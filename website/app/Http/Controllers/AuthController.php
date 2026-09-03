<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterEmployerRequest;
use App\Http\Requests\RegisterSeekerRequest;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\Role;
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

        // The chosen account type must match the account, unless an admin is signing in.
        if ($selectedRole && ! $user->hasRole($selectedRole) && ! $user->hasRole('super-admin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $label = $selectedRole === 'employer' ? 'an Employer' : 'a Job Seeker';

            return back()
                ->withErrors(['email' => "This account is not registered as {$label}. Choose the correct account type and try again."])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Log the login event
        $user = $request->user();
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        // Redirect based on role
        $dashboard = match (true) {
            $user->hasRole('super-admin') => route('admin.dashboard'),
            $user->hasRole('employer')    => route('employer.dashboard'),
            default                       => route('seeker.dashboard'),
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
                    'country_code'       => $data['country_code'],
                    'profile_completion' => 20,
                ]);
                return $user;
            });

            Auth::login($user);

            return redirect()
                ->route('seeker.dashboard')
                ->with('success', 'Your account is ready. Complete your profile to improve matches.');
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
        ]);
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
                    'company_type_id'     => $data['company_type_id'],
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

                return $user;
            });

            Auth::login($user);

            return redirect()
                ->route('employer.dashboard')
                ->with('success', 'Company registration submitted for verification.');
        } catch (\Exception $e) {
            Log::error('Employer registration failed', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }
}