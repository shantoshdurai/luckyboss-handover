# Task 05: Homepage Redesign & Auth Pages

## Context
You are working on **Lucky Boss Portal** at `c:\Luckyboss\luckyboss-app`. Tasks 01-04 have set up the design system, component library, layouts, and backend hardening. Now we rebuild the most important public-facing pages.

**Brand:** Navy `#031f49`, Green `#18a66a`, Accent Blue `#2563eb`. Fonts: Inter (body), Plus Jakarta Sans (headings).

**CRITICAL RULES:**
1. Use ONLY Tailwind CSS utility classes — NO inline `<style>` blocks
2. Use Alpine.js for interactivity
3. Use the `<x-ui.*>` component library from Task 02
4. Use the `<x-layouts.app>` layout from Task 03
5. Every section must be fully responsive (mobile-first)
6. Professional-grade design competing with LinkedIn/Indeed quality

---

## File 1: Homepage

**File**: `c:\Luckyboss\luckyboss-app\resources\views\home.blade.php`

**OVERWRITE** entirely. This is the most important page in the application. It must be stunning.

The controller (`HomeController.php`) passes these variables:
- `$stats` — array: `['activeJobs' => int, 'jobSeekers' => int, 'employers' => int]`
- `$featuredJobs` — Collection of Job models with `->company` relation. Job fields: `title`, `location`, `job_type`, `salary_min`, `salary_max`, `currency_code`, `closing_date`, `company->name`, `company->logo_path`
- `$categories` — Collection of JobCategory models. Fields: `name`, `icon`, `icon_image_path`, `jobs_count` (from withCount)
- `$blogs` — Collection of Blog models. Fields: `title`, `slug`, `category`, `short_description`, `published_at`, `image_path`
- `$slider` — Slider model or null. Fields: `title`, `subtitle`, `button_text`, `button_link`, `image_path`

Design the page with these sections (in order):

### Section 1: Hero
- Full-width navy-to-blue gradient background (`bg-gradient-to-br from-navy via-primary-800 to-accent`)
- Left side: text content, right side: decorative element (CSS shapes/patterns, not images)
- Eyebrow: "AI-POWERED RECRUITMENT PLATFORM"
- H1: Dynamic from `$slider->title` or default "Find the Right Job. Build a Better Career."
- Subtitle from `$slider->subtitle` or default
- Large search form: Keyword input + Location input + Category select + Search button
- Use proper grid layout, responsive (stacks on mobile)
- Stats bar below search: show `$stats` with animated numbers (use `x-data` with `x-intersect` or just static)

### Section 2: Job Categories
- Section title: "Explore **Jobs** by **Category**" (bold words in green)
- Subtitle: "Discover opportunities across industries"
- Grid of category cards (4 cols desktop, 2 tablet, 1 mobile)
- Each card: icon, name, job count, hover effect (lift + shadow)
- Use `$categories` data
- "Browse All Categories" button at bottom

### Section 3: Featured Jobs
- Section title: "Featured **Opportunities**"
- Grid of job cards (3 cols desktop, 2 tablet, 1 mobile)
- Each job card (`<x-ui.card>` with hover):
  - Company logo/avatar fallback
  - Job title (bold)
  - Company name (muted)
  - Location with icon
  - Job type badge (Full-time, Part-time, etc.)
  - Salary range if available
  - "View Role" button
- "View All Jobs" CTA button

### Section 4: How It Works
- Light gray background
- 3 steps for Job Seekers, 3 steps for Employers (tabs or side-by-side)
- Step 1: Register → Step 2: Search/Post → Step 3: Apply/Hire
- Each step: number badge, icon, title, description
- Use `<x-ui.tabs>` component

### Section 5: Statistics / Trust Section
- Full-width navy background
- 4 large stat numbers: Jobs Posted, Companies, Candidates, Placements
- Clean, bold typography
- Subtle animation on scroll

### Section 6: Latest Blog
- Section title: "Career **Knowledge**"
- Grid of 3 blog cards
- Each card: image (or placeholder), category badge, title, excerpt, "Read Article" link, date
- "View All Articles" button

### Section 7: CTA Banner
- Gradient background
- Two-column: Job Seekers CTA (left) + Employers CTA (right)
- "Start Your Job Search" button → Register Seeker
- "Start Hiring Today" button → Register Employer

Use `<x-layouts.app title="Lucky Boss Portal">` wrapper.

**IMPORTANT**: The `<script>` tag at the bottom for search suggestions should be rewritten using Alpine.js `x-data` pattern instead of raw `fetch()`.

---

## File 2: Login Page

**File**: `c:\Luckyboss\luckyboss-app\resources\views\auth\login.blade.php`

**OVERWRITE** entirely.

Design: Split-screen layout.
- Left half: Brand illustration area (navy gradient background with brand messaging, stats, testimonial quote)
- Right half: Login form

```blade
<x-layouts.app title="Sign In — Lucky Boss Portal">
    <div class="min-h-[calc(100vh-72px)] flex">
        {{-- Left: Brand Side --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-navy via-primary-800 to-primary-900 relative overflow-hidden">
            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 text-white">
                <span class="eyebrow text-secondary-300 mb-4">Welcome back</span>
                <h1 class="text-4xl xl:text-5xl font-heading font-bold leading-tight mb-6">
                    Your next career<br>move starts here.
                </h1>
                <p class="text-lg text-slate-300 max-w-md mb-10 leading-relaxed">
                    Sign in to access your dashboard, track applications, and discover opportunities matched to your skills.
                </p>

                {{-- Stats --}}
                <div class="flex gap-8">
                    <div>
                        <div class="text-3xl font-heading font-bold text-secondary-400">5,000+</div>
                        <div class="text-sm text-slate-400 mt-1">Active Jobs</div>
                    </div>
                    <div>
                        <div class="text-3xl font-heading font-bold text-secondary-400">2,500+</div>
                        <div class="text-sm text-slate-400 mt-1">Companies</div>
                    </div>
                    <div>
                        <div class="text-3xl font-heading font-bold text-secondary-400">50,000+</div>
                        <div class="text-sm text-slate-400 mt-1">Job Seekers</div>
                    </div>
                </div>
            </div>

            {{-- Decorative elements --}}
            <div class="absolute top-20 right-0 w-72 h-72 bg-secondary-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 left-10 w-48 h-48 bg-accent/10 rounded-full blur-2xl"></div>
        </div>

        {{-- Right: Form Side --}}
        <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white">
            <div class="w-full max-w-md">
                {{-- Mobile Logo --}}
                <div class="lg:hidden text-center mb-8">
                    <span class="text-2xl font-heading font-bold">
                        <span class="text-secondary-500">Lucky</span><span class="text-navy">Boss</span>
                    </span>
                </div>

                <h2 class="text-2xl font-heading font-bold text-navy mb-2">Sign in to your account</h2>
                <p class="text-text-muted mb-8">
                    Don't have an account?
                    <a href="{{ route('register.seeker') }}" class="text-accent font-semibold hover:underline">Create one free</a>
                </p>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <x-ui.input
                        label="Email Address"
                        name="email"
                        type="email"
                        required
                        placeholder="you@example.com"
                        :value="old('email')"
                        autocomplete="email"
                    />

                    <x-ui.input
                        label="Password"
                        name="password"
                        type="password"
                        required
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    />

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-border text-accent focus:ring-accent">
                            <span class="text-sm text-text-secondary">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-accent font-medium hover:underline">Forgot password?</a>
                    </div>

                    <x-ui.button type="submit" variant="primary" class="w-full" size="lg">
                        Sign In
                    </x-ui.button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-sm text-text-muted">
                        Are you an employer?
                        <a href="{{ route('register.employer') }}" class="text-secondary-500 font-semibold hover:underline">Register your company</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
```

---

## File 3: Job Seeker Registration

**File**: `c:\Luckyboss\luckyboss-app\resources\views\auth\candidate-register.blade.php`

**OVERWRITE** entirely. Same split-screen design as login but:
- Left side: messaging about job seekers ("Find your dream career", stats about jobs available)
- Right side: Registration form with fields:
  - Full Name
  - Email
  - Phone Number
  - Country (select from `$countries`)
  - Password
  - Confirm Password
  - Terms checkbox
  - "Create Account" button
- Link to login page at bottom
- Link to employer registration

Use `<x-ui.input>`, `<x-ui.select>`, `<x-ui.button>` components.
The controller passes `$countries` — a collection with `code` and `name` fields.

---

## File 4: Employer Registration

**File**: `c:\Luckyboss\luckyboss-app\resources\views\auth\employer-register.blade.php`

**OVERWRITE** entirely. Same split-screen design but:
- Left side: messaging about employers ("Hire the best talent", stats about candidate pool)
- Right side: Registration form with fields:
  - Full Name (contact person)
  - Email
  - Phone Number
  - Country (select from `$countries`)
  - Company Name
  - Company Type (select from `$types` — has `id` and `name`)
  - Registration Number (optional)
  - Password
  - Confirm Password
  - Terms checkbox
  - "Register Company" button
- Link to login at bottom
- Link to job seeker registration

The controller passes `$types` (CompanyType collection with `id`, `name`) and `$countries`.

---

## Verification

After completing all files:
1. Run `npm run build` — must succeed
2. Visit the homepage — should render the new design
3. Visit `/login` — should show split-screen login
4. Visit `/register/job-seeker` — should show registration form
5. Visit `/register/employer` — should show employer registration

## IMPORTANT
- ALL data comes from the existing controllers — do NOT modify any PHP files in this task
- Use `{{ route('...') }}` for ALL links — use the route names from `routes/web.php`
- The homepage currently receives `$categories` with a loaded `jobs` relation. After Task 04, it will have `jobs_count` attribute instead. Support BOTH: use `$category->jobs_count ?? $category->jobs->count()` for the count
- Handle `$slider` being null gracefully with fallback text
- Handle empty collections with `@forelse` / `@empty` patterns using `<x-ui.empty-state>`
