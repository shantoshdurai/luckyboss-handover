<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterEmployerRequest;
use App\Http\Requests\RegisterSeekerRequest;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\Package;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(Request $request): View
    {
        return view('auth.login', [
            'adminLogin' => $request->routeIs('admin.login'),
        ]);
    }

    /**
     * Handle login attempt.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The supplied credentials are invalid.'])
                ->onlyInput('email');
        }

        $user = Auth::user();
        $selectedRole = $request->input('login_as');

        /*
            The "I am a" toggle is a preference, not a gate.

            It used to be a gate, and it refused correct credentials: the sign-in
            page pre-selects "Job seeker", so every employer who typed the right
            email and password without noticing the toggle was signed out again
            and told "This account is not registered as a Job Seeker." Nothing
            was wrong with the account — the page had answered a question on
            their behalf and then held them to the answer.

            The toggle earns its place only for someone who holds both roles, and
            it can do that without being able to refuse anyone: honour it when the
            account actually has that role, otherwise land on the role the account
            does have. Nobody is ever turned away for picking the wrong door.
        */
        $landAs = match (true) {
            $user->hasRole('super-admin')                  => 'super-admin',
            $selectedRole && $user->hasRole($selectedRole)  => $selectedRole,
            $user->hasRole('employer')                      => 'employer',
            default                                         => 'job-seeker',
        };

        $request->session()->regenerate();

        // Log the login event
        $user = $request->user();
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        // Redirect based on the role decided above.
        $dashboard = match ($landAs) {
            'super-admin' => route('admin.dashboard'),
            // The hiring agent, not the dashboard — the same landing an
            // employer gets from the logo, and the same shape a candidate gets
            // from seeker.home.
            'employer'    => route('employer.home'),
            // Candidates land on Lucky AI, not a dashboard of empty panels.
            // A new candidate has no applications, no matches and no saved jobs,
            // so the dashboard's first impression was six zeroes; the agent
            // instead asks the four things that turn the zeroes into matches.
            default       => route('seeker.home'),
        };

        // Clear any mismatched stale intended URLs across different role sessions
        $request->session()->forget('url.intended');

        return redirect($dashboard)->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Show job seeker registration form.
     */
    public function showCandidateRegister(): View
    {
        return view('auth.candidate-register', [
            'countries' => Country::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Register a new job seeker.
     */
    public function registerCandidate(RegisterSeekerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $user = DB::transaction(function () use ($data) {
                $user = User::create($data);
                $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));
                CandidateProfile::create([
                    'user_id'            => $user->id,
                    // Nullable, and no longer collected at sign-up. Reading the
                    // key directly would throw the moment the field left the
                    // form, exactly as `company_type_id` did on the employer side.
                    'country_code'       => $data['country_code'] ?? null,
                    'profile_completion' => 20,
                ]);
                return $user;
            });

            Auth::login($user);

            return redirect()
                ->route('seeker.home')
                ->with('success', 'Your account is ready.');
        } catch (\Exception $e) {
            Log::error('Candidate registration failed', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    /**
     * Show employer registration form.
     */
    public function showEmployerRegister(): View
    {
        return view('auth.employer-register', [
            'types'     => CompanyType::where('is_active', true)->orderBy('name')->get(),
            'countries' => Country::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            // Priced from `package_prices`, never converted live — §64, and the
            // reason BUSINESS_MODEL.md keeps three stored rows per package
            // rather than one and an FX call.
            'packages'  => Package::with('prices')->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    /**
     * The plan the employer chose, made real.
     *
     * There is no payment gateway yet — deliberately, per BUSINESS_MODEL.md —
     * so this activates the plan unpaid and the registration form says so in as
     * many words. The alternative was to record the choice as "requested" and
     * leave the allowances at zero, which would land every new employer in a
     * portal that refuses to post a job.
     *
     * Nothing here issues credits directly: `SubscriptionEntitlementService`
     * writes the monthly `plan_grant` lazily the first time a balance is read,
     * so an active subscription row with a package on it is the whole of what
     * this needs to do.
     */
    private function startSubscription(Company $company, Package $package): void
    {
        $price = $package->prices
            ->firstWhere('currency_code', $this->currencyFor($company->country_code));

        Subscription::create([
            'company_id'    => $company->id,
            'package_id'    => $package->id,
            'status'        => 'active',
            'starts_at'     => today(),
            'expires_at'    => today()->addDays($package->validity_days ?: 30),
            'entitlements'  => $package->entitlements,
            'currency_code' => $price?->currency_code ?? 'SGD',
            // Zero, and honestly zero: nothing has been charged. Writing the
            // list price here would put a payment in the ledger that never
            // happened.
            'amount'        => 0,
        ]);
    }

    /**
     * Which of the three stored prices applies. Countries are the ones the
     * platform actually serves; anything else falls back to SGD, the currency
     * the company is registered against.
     */
    private function currencyFor(?string $countryCode): string
    {
        return match (strtoupper((string) $countryCode)) {
            'IN', 'IND' => 'INR',
            'MY', 'MYS' => 'MYR',
            default     => 'SGD',
        };
    }

    /**
     * Register a new employer with company.
     */
    public function registerEmployer(RegisterEmployerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $user = DB::transaction(function () use ($data) {
                $user = User::create(
                    collect($data)->only(['name', 'email', 'phone', 'password', 'country_code'])->all()
                );
                $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

                $company = Company::create([
                    'name'                => $data['company_name'],
                    // `company_type_id` is nullable, so validated() simply does
                    // not contain the key when the field is left blank. Reading
                    // it directly threw "Undefined array key", which the catch
                    // below swallowed into "Registration failed. Please try
                    // again." — an employer who skipped the optional dropdown
                    // could never sign up, and was told nothing useful about why.
                    'company_type_id'     => $data['company_type_id'] ?? null,
                    'country_code'        => $data['country_code'],
                    'registration_number' => $data['registration_number'] ?? null,
                    'email'               => $data['email'],
                    'phone'               => $data['phone'],
                    'status'              => 'pending',
                ]);

                $company->users()->attach($user->id, [
                    'company_role' => 'company-admin',
                    'is_active'    => true,
                ]);

                // The plan chosen on step 4. Optional at the request level so an
                // old cached form, or a client that never sent the field, still
                // creates a working account rather than a 422 nobody can fix.
                if (! empty($data['package_id'])) {
                    $package = Package::find($data['package_id']);
                    if ($package && $package->is_active) {
                        $this->startSubscription($company, $package);
                    }
                }

                return $user;
            });

            Auth::login($user);

            return redirect()
                ->route('employer.home')
                ->with('success', 'Company registration submitted for verification.');
        } catch (\Exception $e) {
            Log::error('Employer registration failed', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }
}