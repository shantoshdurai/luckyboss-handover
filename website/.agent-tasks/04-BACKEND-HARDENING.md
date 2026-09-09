# Task 04: Backend Architecture Hardening

## Context
You are working on **Lucky Boss Portal** at `c:\Luckyboss\luckyboss-app`. Laravel 12 + MySQL. The backend has critical issues:

1. **No Form Requests** — validation is inline in controllers
2. **Insecure file uploads** — using `move()` instead of `Storage` facade
3. **No pagination** — list views use `->get()` instead of `->paginate()`
4. **No caching** — homepage hits DB on every request
5. **Weak auth** — email verification commented out, inconsistent API validation
6. **Bad middleware** — `EnsurePermission` returns 403 even for unauthenticated users

**Key existing files:**
- `app/Http/Controllers/AuthController.php` — handles login, register for seeker and employer
- `app/Http/Controllers/HomeController.php` — homepage data loading
- `app/Http/Controllers/Api/V1/AuthController.php` — API auth
- `app/Http/Middleware/EnsurePermission.php` — permission check
- `app/Http/Middleware/EnsureFeatureEnabled.php` — feature flag check
- `app/Models/User.php` — `MustVerifyEmail` is commented out
- `app/Services/NotificationService.php` — only writes to DB
- 25 admin controllers in `app/Http/Controllers/Admin/`
- 4 employer controllers, 4 seeker controllers

---

## Step 1: Create Form Requests

Create these files in `c:\Luckyboss\luckyboss-app\app\Http\Requests\`:

### `LoginRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ];
    }
}
```

### `RegisterSeekerRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSeekerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:120'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'        => ['required', 'string', 'max:32', 'unique:users,phone'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'An account with this email already exists.',
            'phone.unique'     => 'An account with this phone number already exists.',
            'password.min'     => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ];
    }
}
```

### `RegisterEmployerRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterEmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:120'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'               => ['required', 'string', 'max:32', 'unique:users,phone'],
            'country_code'        => ['required', 'string', 'size:2', 'exists:countries,code'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
            'company_name'        => ['required', 'string', 'max:180'],
            'company_type_id'     => ['nullable', 'integer', 'exists:company_types,id'],
            'registration_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'An account with this email already exists.',
            'phone.unique'        => 'An account with this phone number already exists.',
            'company_name.required' => 'Please enter your company name.',
        ];
    }
}
```

### `StoreJobRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:200'],
            'category_id'      => ['required', 'integer', 'exists:job_categories,id'],
            'description'      => ['required', 'string', 'min:50'],
            'requirements'     => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'location'         => ['required', 'string', 'max:255'],
            'country_code'     => ['required', 'string', 'exists:countries,code'],
            'job_type'         => ['required', 'string', 'in:full-time,part-time,contract,internship,temporary'],
            'work_mode'        => ['nullable', 'string', 'in:onsite,remote,hybrid'],
            'experience_min'   => ['nullable', 'integer', 'min:0'],
            'experience_max'   => ['nullable', 'integer', 'min:0', 'gte:experience_min'],
            'salary_min'       => ['nullable', 'numeric', 'min:0'],
            'salary_max'       => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'currency_code'    => ['nullable', 'string', 'exists:currencies,code'],
            'vacancies'        => ['nullable', 'integer', 'min:1'],
            'closing_date'     => ['nullable', 'date', 'after:today'],
            'skills'           => ['nullable', 'string'],
            'image'            => ['nullable', 'image', 'max:2048'],
        ];
    }
}
```

---

## Step 2: Create FileUploadService

**File**: `c:\Luckyboss\luckyboss-app\app\Services\FileUploadService.php`

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload a file to the specified directory.
     *
     * @param UploadedFile $file
     * @param string $directory  e.g. 'branding', 'resumes', 'company-logos', 'job-images', 'blog-images'
     * @param string|null $oldPath  Previous file path to delete
     * @return string  The stored file path (relative)
     */
    public function upload(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        // Delete old file if provided
        if ($oldPath) {
            $this->delete($oldPath);
        }

        // Generate unique filename
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . now()->format('YmdHis')
            . '-' . Str::random(6)
            . '.' . $file->getClientOriginalExtension();

        // Store in public disk
        $path = $file->storeAs(
            "uploads/{$directory}",
            $filename,
            'public'
        );

        return $path;
    }

    /**
     * Upload and optimize an image.
     */
    public function uploadImage(UploadedFile $file, string $directory, ?string $oldPath = null, int $maxWidth = 1200): string
    {
        return $this->upload($file, $directory, $oldPath);
    }

    /**
     * Upload a resume/document.
     */
    public function uploadDocument(UploadedFile $file, string $directory = 'resumes', ?string $oldPath = null): string
    {
        // Validate allowed extensions
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('Only PDF, DOC, and DOCX files are allowed.');
        }

        return $this->upload($file, $directory, $oldPath);
    }

    /**
     * Delete a file.
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * Get the public URL for a stored file.
     */
    public function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
```

---

## Step 3: Update AuthController to use Form Requests

**File**: `c:\Luckyboss\luckyboss-app\app\Http\Controllers\AuthController.php`

**OVERWRITE** entirely:

```php
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
    public function showLogin(): View
    {
        return view('auth.login');
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

        return redirect()->intended($dashboard);
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
```

---

## Step 4: Update HomeController with Caching

**File**: `c:\Luckyboss\luckyboss-app\app\Http\Controllers\HomeController.php`

**OVERWRITE** entirely:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'stats' => Cache::remember('home.stats', 900, function () {
                return [
                    'activeJobs' => Job::where('status', 'published')->count(),
                    'jobSeekers' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->count(),
                    'employers'  => Company::where('status', 'verified')->count(),
                ];
            }),

            'featuredJobs' => Cache::remember('home.featured_jobs', 600, function () {
                return Job::with('company')
                    ->where('status', 'published')
                    ->latest('published_at')
                    ->take(6)
                    ->get();
            }),

            'categories' => Cache::remember('home.categories', 600, function () {
                return JobCategory::withCount(['jobs' => fn ($q) => $q->where('status', 'published')])
                    ->where('show_on_home', true)
                    ->orderBy('sort_order')
                    ->take(8)
                    ->get();
            }),

            'blogs' => Cache::remember('home.blogs', 1800, function () {
                return Blog::where('is_published', true)
                    ->latest('published_at')
                    ->take(3)
                    ->get();
            }),

            'slider' => Cache::remember('home.slider', 600, function () {
                return Slider::where('is_active', true)
                    ->where('web_enabled', true)
                    ->orderBy('sort_order')
                    ->first();
            }),
        ]);
    }
}
```

**NOTE**: Changed `JobCategory::with('jobs')` to `JobCategory::withCount(['jobs' => ...])` to avoid loading ALL jobs into memory just to count them. This is a huge performance fix.

---

## Step 5: Fix EnsurePermission Middleware

**File**: `c:\Luckyboss\luckyboss-app\app\Http\Middleware\EnsurePermission.php`

Read the existing file first, then update it to:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Return 401 if not authenticated
        if (!$request->user()) {
            abort(401, 'Authentication required.');
        }

        // Return 403 if authenticated but lacks permission
        if (!$request->user()->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
```

---

## Step 6: Create SubscriptionCheck Middleware

**File**: `c:\Luckyboss\luckyboss-app\app\Http\Middleware\SubscriptionCheck.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionCheck
{
    /**
     * Check if the employer has an active subscription.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('employer')) {
            return $next($request);
        }

        $company = $user->companies()->first();

        if (!$company) {
            return redirect()->route('employer.dashboard')
                ->with('error', 'No company profile found. Please contact support.');
        }

        // Check for active subscription
        $hasActiveSubscription = $company->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();

        if (!$hasActiveSubscription) {
            // Allow access to certain routes even without subscription
            $allowedRoutes = [
                'employer.dashboard',
                'employer.portal', // billing section
                'employer.company-profile.update',
            ];

            $currentRoute = $request->route()?->getName();

            // Allow billing/subscription related portal sections
            if ($currentRoute === 'employer.portal' && in_array($request->route('section'), ['billing', 'company-profile'])) {
                return $next($request);
            }

            if (!in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('employer.dashboard')
                    ->with('error', 'Your subscription has expired. Please renew to access this feature.');
            }
        }

        return $next($request);
    }
}
```

---

## Step 7: Add Pagination to Admin Controllers

Read each controller file in `app/Http/Controllers/Admin/` and find any method that uses `->get()` for listing data. Replace with `->paginate(20)`.

**Key files to check and fix:**

1. **`Admin/CompanyController.php`** — `index()` method
2. **`Admin/CandidateOperationsController.php`** — `index()` method
3. **`Admin/JobController.php`** — `index()` method
4. **`Admin/PaymentController.php`** — `index()` method
5. **`Admin/SubscriptionController.php`** — `index()` method
6. **`Admin/BlogController.php`** — `index()` method
7. **`Admin/RecruitmentController.php`** — `index()` method

For each file:
- Open it, find the `index()` method
- Change `->get()` to `->paginate(20)`
- Make sure the corresponding Blade view handles pagination by adding `{{ $variableName->links() }}` at the bottom of the listing

Also check:
8. **`Employer/JobController.php`** — `index()` should paginate
9. **`PublicPortalController.php`** — `jobs()` method should paginate

---

## Step 8: Register the SubscriptionCheck Middleware

**File**: `c:\Luckyboss\luckyboss-app\bootstrap\app.php`

Read this file and add the middleware alias. Look for where middleware is registered and add:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\EnsurePermission::class,
        'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        'subscription' => \App\Http\Middleware\SubscriptionCheck::class,
    ]);
})
```

If the file already has `withMiddleware`, just add the `subscription` alias to the existing array.

---

## Verification

1. Run `php artisan route:list` — should not error
2. Run `php artisan config:cache` — should not error
3. The Form Request files should be loadable: `php artisan tinker` then `new \App\Http\Requests\LoginRequest()`

## IMPORTANT RULES
1. Do NOT delete any existing controller methods — only modify them
2. Keep ALL existing route names working
3. Keep ALL existing view references working
4. When adding pagination, make sure views can handle both paginated and non-paginated data
5. Preserve all existing comments and docstrings that are unrelated to your changes
