<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Blog;
use App\Models\CompanyGrade;
use App\Models\CompanyType;
use App\Models\FeatureFlag;
use App\Models\Job;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ApiIntegration;
use App\Models\PlatformNotification;
use App\Models\Slider;
use App\Models\Subscription;
use App\Models\AdminRecord;
use App\Models\ExternalSource;
use App\Models\ImportBatch;
use App\Models\SupportTicket;
use App\Models\Invoice;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\Country;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Operations Admin', 'slug' => 'operations-admin'],
            ['name' => 'Recruitment Manager', 'slug' => 'recruitment-manager'],
            ['name' => 'Finance Admin', 'slug' => 'finance-admin'],
            ['name' => 'Content Manager', 'slug' => 'content-manager'],
            ['name' => 'Support Agent', 'slug' => 'support-agent'],
            ['name' => 'API Manager', 'slug' => 'api-manager'],
            ['name' => 'Employer', 'slug' => 'employer'],
            ['name' => 'Job Seeker', 'slug' => 'job-seeker'],
        ])->mapWithKeys(fn (array $role) => [$role['slug'] => Role::firstOrCreate(['slug' => $role['slug']], $role)]);

        $admin = User::firstOrCreate(['email' => 'admin@luckyboss.test'], ['name' => 'Luckyboss Admin', 'password' => 'password', 'country_code' => 'SG']);
        $admin->roles()->syncWithoutDetaching([$roles['super-admin']->id]);

        $permissions = collect(['employers.manage','candidates.manage','jobs.manage','recruitment.manage','subscriptions.manage','payments.manage','cms.manage','support.manage','api.manage','reports.view','settings.manage','security.manage'])->mapWithKeys(function (string $slug) { $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => str($slug)->replace('.', ' ')->headline(), 'module' => str($slug)->before('.')]); return [$slug => $permission]; });
        $rolePermissions = ['operations-admin'=>['employers.manage','candidates.manage','jobs.manage','recruitment.manage','reports.view'], 'recruitment-manager'=>['candidates.manage','jobs.manage','recruitment.manage','reports.view'], 'finance-admin'=>['subscriptions.manage','payments.manage','reports.view'], 'content-manager'=>['cms.manage'], 'support-agent'=>['support.manage'], 'api-manager'=>['api.manage','settings.manage']];
        foreach ($rolePermissions as $role => $permissionSlugs) { $roles[$role]->permissions()->syncWithoutDetaching($permissions->only($permissionSlugs)->pluck('id')->all()); }

        $types = collect(['Recruitment Agency', 'Construction', 'Manufacturing', 'Logistics', 'Warehouse', 'Healthcare', 'Hospitality', 'IT', 'Retail'])->map(fn (string $name) => CompanyType::firstOrCreate(['slug' => str($name)->slug()], ['name' => $name]));
        $grades = collect(['Standard', 'Silver', 'Gold', 'Premium', 'Corporate', 'Enterprise'])->map(fn (string $name) => CompanyGrade::firstOrCreate(['slug' => str($name)->slug()], ['name' => $name]));

        foreach ([
            ['key' => 'platform_ai_enabled', 'name' => 'Platform AI', 'is_enabled' => false],
            ['key' => 'employer_byoai_enabled', 'name' => 'Employer BYOAI', 'is_enabled' => true],
            ['key' => 'ai_matching_enabled', 'name' => 'AI Matching', 'is_enabled' => false],
            ['key' => 'external_jobs_enabled', 'name' => 'External Jobs', 'is_enabled' => false],
            ['key' => 'external_candidates_enabled', 'name' => 'External Candidates', 'is_enabled' => false],
            ['key' => 'candidate_monetization_enabled', 'name' => 'Candidate Monetization', 'is_enabled' => false],
            ['key' => 'google_calendar_enabled', 'name' => 'Google Calendar', 'is_enabled' => false],
            // Spec section 3 names each AI feature separately so an admin can
            // run the chatbot while leaving letter generation off, or the
            // reverse. One master switch would not allow that.
            ['key' => 'ai_offer_letter_enabled', 'name' => 'AI Offer Letter Generator', 'is_enabled' => true],
            ['key' => 'ai_interview_letter_enabled', 'name' => 'AI Interview Letter Generator', 'is_enabled' => true],
            ['key' => 'ai_email_generator_enabled', 'name' => 'AI Email Generator', 'is_enabled' => true],
        ] as $flag) {
            FeatureFlag::updateOrCreate(['key' => $flag['key']], $flag);
        }

        $company = Company::firstOrCreate(['name' => 'Luckyboss Demo Recruitment'], ['email' => 'hello@luckyboss.test', 'country_code' => 'SG', 'status' => 'verified', 'industry' => 'Recruitment', 'company_type_id' => $types->firstWhere('name', 'Recruitment Agency')->id, 'company_grade_id' => $grades->firstWhere('name', 'Premium')->id]);
        foreach ([['code'=>'SGD','name'=>'Singapore Dollar','symbol'=>'S$'],['code'=>'INR','name'=>'Indian Rupee','symbol'=>'Rs'],['code'=>'MYR','name'=>'Malaysian Ringgit','symbol'=>'RM']] as $currency) { \App\Models\Currency::updateOrCreate(['code'=>$currency['code']],$currency); }
        foreach ([['code'=>'IN','name'=>'India'],['code'=>'SG','name'=>'Singapore']] as $country) { Country::updateOrCreate(['code'=>$country['code']], $country + ['sort_order' => 1, 'is_active' => true]); }
        $plans=[]; foreach ([['Starter',99,['job_posts'=>5,'candidate_views'=>50,'ai_matching'=>false,'ai_usage'=>0,'byoai'=>true]],['Professional',299,['job_posts'=>25,'candidate_views'=>500,'ai_matching'=>true,'ai_usage'=>200,'byoai'=>true]],['Enterprise',799,['job_posts'=>-1,'candidate_views'=>-1,'ai_matching'=>true,'ai_usage'=>-1,'external_candidates'=>true,'byoai'=>true]]] as [$name,$price,$entitlements]) { $plans[$name]=Package::updateOrCreate(['slug'=>str($name)->slug()],['name'=>$name,'description'=>"{$name} employer recruitment package",'validity_days'=>30,'entitlements'=>$entitlements,'is_active'=>true]); $plans[$name]->prices()->updateOrCreate(['currency_code'=>'SGD'],['amount'=>$price,'tax_rate'=>0]); }
        $subscription=Subscription::updateOrCreate(['company_id'=>$company->id,'package_id'=>$plans['Professional']->id],['status'=>'active','starts_at'=>today(),'expires_at'=>today()->addDays(90),'entitlements'=>$plans['Professional']->entitlements,'currency_code'=>'SGD','amount'=>299]);
        Payment::firstOrCreate(['reference'=>'LB-DEMO-001'],['company_id'=>$company->id,'subscription_id'=>$subscription->id,'purpose'=>'subscription','gateway'=>'manual','status'=>'paid','currency_code'=>'SGD','amount'=>299,'paid_at'=>now()]);
        $payment=Payment::where('reference','LB-DEMO-001')->first(); Invoice::firstOrCreate(['number'=>'INV-LB-0001'],['payment_id'=>$payment->id,'company_id'=>$company->id,'number'=>'INV-LB-0001','type'=>'employer','status'=>'issued','currency_code'=>'SGD','amount'=>299]);
        foreach ([['module'=>'email-templates','name'=>'Interview Invitation','description'=>'Default interview invitation template'],['module'=>'notification-sounds','name'=>'Application Update','description'=>'Job seeker sound payload'],['module'=>'mobile-app-settings','name'=>'Job Seeker Android','description'=>'Minimum version and store URL'],['module'=>'home-sections','name'=>'Featured Jobs','description'=>'Website and app home section'],['module'=>'location-masters','name'=>'Singapore','description'=>'Country master'],['module'=>'general-settings','name'=>'Portal Branding','description'=>'Logo and primary color settings']] as $record){AdminRecord::updateOrCreate(['module'=>$record['module'],'slug'=>str($record['name'])->slug()],$record+['slug'=>str($record['name'])->slug(),'payload'=>[],'is_active'=>true]);}
        $source=ExternalSource::firstOrCreate(['name'=>'Demo Recruitment Partner'],['source_type'=>'Recruitment Partner','feed_type'=>'manual','status'=>'active','contacts_visible'=>false,'import_limit'=>100]); ImportBatch::firstOrCreate(['external_source_id'=>$source->id,'data_type'=>'candidates'],['user_id'=>$admin->id,'status'=>'completed','records_received'=>10,'records_imported'=>8,'records_failed'=>2]);
        foreach ([['key'=>'platform_openai','name'=>'OpenAI GPT','provider'=>'OpenAI','is_enabled'=>false,'monthly_limit'=>10000000],['key'=>'resume_parser','name'=>'Resume Parser','provider'=>'Manual fallback','is_enabled'=>false,'monthly_limit'=>5000],['key'=>'payment_gateway','name'=>'Payment Gateway','provider'=>'Manual / Stripe ready','is_enabled'=>false,'monthly_limit'=>null],['key'=>'whatsapp','name'=>'WhatsApp','provider'=>'Cloud API','is_enabled'=>false,'monthly_limit'=>100000]] as $integration) { ApiIntegration::updateOrCreate(['key'=>$integration['key']],$integration); }
        Slider::updateOrCreate(['title'=>'Find the Right Job. Build a Better Career.'],['subtitle'=>'AI-powered recruitment for Singapore, Malaysia, India & More.','cta_text'=>'Search Jobs','cta_url'=>'/#jobs','sort_order'=>1,'web_enabled'=>true,'app_enabled'=>true,'is_active'=>true]);
        $categoryIcons = ['Construction' => 'hard-hat', 'Manufacturing' => 'factory', 'Warehouse' => 'warehouse', 'Healthcare' => 'heart-pulse', 'Logistics' => 'truck', 'Hospitality' => 'utensils', 'Domestic Worker' => 'house', 'Engineering' => 'settings-2', 'Sales' => 'handshake', 'Administration' => 'clipboard-list', 'Security' => 'shield-check'];
        $categories = collect(array_keys($categoryIcons))->map(function (string $name, int $index) use ($categoryIcons) {
            return JobCategory::updateOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'icon' => $categoryIcons[$name], 'icon_image_path' => 'images/lucky-boss-logo.png', 'sort_order' => $index + 1, 'show_on_home' => true, 'is_active' => true]);
        });
        $job = Job::firstOrCreate(['company_id' => $company->id, 'title' => 'Warehouse Supervisor'], ['description' => 'Lead warehouse operations and coordinate a high-performing team.', 'country_code' => 'SG', 'location' => 'Singapore', 'job_category_id' => $categories->firstWhere('name', 'Warehouse')->id, 'experience_min' => 3, 'experience_max' => 5, 'salary_min' => 3000, 'salary_max' => 4500, 'currency_code' => 'SGD', 'status' => 'published', 'is_featured' => true, 'published_at' => now(), 'closing_date' => now()->addMonth()]);
                $dummyJobs = [
            ['title'=>'Warehouse Coordinator','category'=>'Warehouse','location'=>'Jurong East','country_code'=>'SG','salary_min'=>2800,'salary_max'=>3800],
            ['title'=>'Construction Site Supervisor','category'=>'Construction','location'=>'Kallang','country_code'=>'SG','salary_min'=>4200,'salary_max'=>5800],
            ['title'=>'Logistics Operations Executive','category'=>'Logistics','location'=>'Tuas','country_code'=>'SG','salary_min'=>3200,'salary_max'=>4600],
            ['title'=>'Manufacturing Quality Engineer','category'=>'Manufacturing','location'=>'Shah Alam','country_code'=>'MY','salary_min'=>4500,'salary_max'=>6500],
            ['title'=>'Healthcare Assistant','category'=>'Healthcare','location'=>'Singapore','country_code'=>'SG','salary_min'=>2400,'salary_max'=>3400],
            ['title'=>'Hospitality Front Office Manager','category'=>'Hospitality','location'=>'Orchard','country_code'=>'SG','salary_min'=>3600,'salary_max'=>5000],
            ['title'=>'Recruitment Consultant','category'=>'Recruitment Agency','location'=>'Chennai','country_code'=>'IN','salary_min'=>3000,'salary_max'=>5000],
            ['title'=>'Retail Store Manager','category'=>'Retail','location'=>'Kuala Lumpur','country_code'=>'MY','salary_min'=>3800,'salary_max'=>5600],
            ['title'=>'IT Support Specialist','category'=>'IT','location'=>'Paya Lebar','country_code'=>'SG','salary_min'=>3500,'salary_max'=>5200],
            ['title'=>'Warehouse Picker and Packer','category'=>'Warehouse','location'=>'Woodlands','country_code'=>'SG','salary_min'=>2200,'salary_max'=>3000],
        ];
        foreach ($dummyJobs as $index => $dummy) {
            $dummyCategory = JobCategory::where('name', $dummy['category'])->first() ?: $categories->first();
            Job::firstOrCreate(['company_id' => $company->id, 'title' => $dummy['title']], [
                'description' => "Test job listing for {$dummy['title']}.", 'image_path' => 'images/lucky-boss-logo.png', 'country_code' => $dummy['country_code'], 'location' => $dummy['location'], 'job_category_id' => $dummyCategory?->id, 'experience_min' => $index % 4, 'experience_max' => ($index % 4) + 3, 'salary_min' => $dummy['salary_min'], 'salary_max' => $dummy['salary_max'], 'currency_code' => $dummy['country_code'] === 'IN' ? 'INR' : ($dummy['country_code'] === 'MY' ? 'MYR' : 'SGD'), 'status' => 'published', 'is_featured' => $index < 3, 'is_urgent' => $index === 3, 'published_at' => now()->subDays($index), 'closing_date' => now()->addDays(30 + $index),
            ]);
        }
        Job::whereNull('image_path')->update(['image_path' => 'images/lucky-boss-logo.png']);
        $employer = User::firstOrCreate(['email' => 'employer@luckyboss.test'], ['name' => 'Arun Kumar', 'phone' => '+6591234567', 'country_code' => 'SG', 'password' => 'password']);
        $employer->roles()->syncWithoutDetaching([$roles['employer']->id]); $company->users()->syncWithoutDetaching([$employer->id => ['company_role' => 'company-admin', 'is_active' => true]]);
        $candidate = User::firstOrCreate(['email' => 'candidate@luckyboss.test'], ['name' => 'Maya Tan', 'phone' => '+6587654321', 'country_code' => 'SG', 'password' => 'password']);
        $candidate->roles()->syncWithoutDetaching([$roles['job-seeker']->id]); $candidate->candidateProfile()->firstOrCreate([], ['country_code' => 'SG', 'current_title' => 'Warehouse Coordinator', 'current_location' => 'Singapore', 'preferred_location' => 'Singapore', 'years_experience' => 4, 'profile_completion' => 65]);
        // A candidate who has actually applied.
        //
        // The seeder imported JobApplication and never created one, so the
        // employer pipeline and the admin recruitment screens seeded empty —
        // and four tests that call JobApplication::firstOrFail() could never
        // pass. It also meant a fresh install showed an employer a pipeline
        // with nothing in it, which reads as broken rather than as new.
        JobApplication::firstOrCreate(
            ['job_id' => $job->id, 'candidate_id' => $candidate->id],
            [
                'source' => 'website',
                'status' => 'Applied',
                'match_score' => 82,
                'applied_at' => now()->subDays(2),
                'last_activity_at' => now()->subDays(2),
            ]
        );

        SupportTicket::firstOrCreate(['subject'=>'Need help with job application'],['user_id'=>$candidate->id,'source'=>'website','message'=>'Please advise on completing my profile.','status'=>'new','priority'=>'normal']);
        PlatformNotification::firstOrCreate(['user_id' => $candidate->id, 'title' => 'Your application was shortlisted'], [
            'type' => 'application_update',
            'body' => 'Luckyboss Demo Recruitment shortlisted you for Warehouse Supervisor.',
            'sound' => 'application_update',
            'created_at' => now()->subMinutes(15),
        ]);
        PlatformNotification::firstOrCreate(['user_id' => $candidate->id, 'title' => 'Interview Scheduled'], [
            'type' => 'interview_alert',
            'body' => 'Tuas Port Logistics confirmed an interview for Warehouse Supervisor tomorrow at 10:00 AM.',
            'sound' => 'interview_alert',
            'created_at' => now()->subHours(2),
        ]);
        PlatformNotification::firstOrCreate(['user_id' => $candidate->id, 'title' => 'New Top Job Match (94%)'], [
            'type' => 'job_match',
            'body' => 'Jurong Global Freight posted a matching Logistics Lead position.',
            'sound' => 'job_match',
            'created_at' => now()->subHours(5),
        ]);

        // Employer Notifications
        PlatformNotification::firstOrCreate(['user_id' => $employer->id, 'title' => 'New Applicant: Maya Tan'], [
            'type' => 'applicant_alert',
            'body' => 'Maya Tan applied for Warehouse Supervisor (88% Match Score).',
            'sound' => 'applicant_alert',
            'created_at' => now()->subMinutes(25),
        ]);
        PlatformNotification::firstOrCreate(['user_id' => $employer->id, 'title' => 'Enterprise Package Active'], [
            'type' => 'payment_alert',
            'body' => 'Your 30-day Enterprise Recruitment subscription has been activated successfully.',
            'sound' => 'payment_alert',
            'created_at' => now()->subHours(3),
        ]);

        // Super Admin Notifications
        PlatformNotification::firstOrCreate(['user_id' => $admin->id, 'title' => 'Employer KYC Verification Needed'], [
            'type' => 'system_alert',
            'body' => 'Keppel Civil & Infrastructure submitted business registration for review.',
            'sound' => 'system_alert',
            'created_at' => now()->subMinutes(10),
        ]);
        PlatformNotification::firstOrCreate(['user_id' => $admin->id, 'title' => 'Subscription Renewal Received'], [
            'type' => 'payment_alert',
            'body' => 'Tuas Port Logistics renewed Enterprise Package ($1,299.00 SGD).',
            'sound' => 'payment_alert',
            'created_at' => now()->subHours(1),
        ]);
        PlatformNotification::firstOrCreate(['user_id' => $admin->id, 'title' => 'System Health Check Clean'], [
            'type' => 'system_alert',
            'body' => 'All background queues, AI models, and database connections are operating nominally.',
            'sound' => 'system_alert',
            'created_at' => now()->subHours(4),
        ]);
        foreach ([
            ['title' => 'How to Prepare for a Warehouse Supervisor Interview', 'category' => 'Interview Tips', 'short_description' => 'Practical preparation tips for leading warehouse teams and demonstrating operational confidence.', 'content' => 'A strong warehouse supervisor interview begins with specific examples. Prepare to explain how you improve safety, plan shifts, manage stock accuracy, and support your team through busy periods.', 'author' => 'Luckyboss Team'],
            ['title' => 'Creating a Resume That Gets Noticed', 'category' => 'Resume Tips', 'short_description' => 'A clear, focused resume helps employers quickly understand your experience and potential.', 'content' => 'Lead with your most relevant experience, use measurable outcomes where possible, and tailor your skills to the role you want. Keep your contact details current and make your availability clear.', 'author' => 'Luckyboss Team'],
            ['title' => 'Building a More Effective Hiring Pipeline', 'category' => 'Employer Guides', 'short_description' => 'Simple habits that help employers turn applications into confident hiring decisions.', 'content' => 'Define the outcome for every hiring stage, respond quickly to suitable applicants, and keep candidates informed. A consistent process improves both hiring quality and employer reputation.', 'author' => 'Luckyboss Team'],
            ['title' => 'How to Write Better Job Descriptions', 'category' => 'Employer Guides', 'short_description' => 'Clear job descriptions attract better matched applicants.', 'content' => 'Describe the outcome of the role, the essential skills, the working arrangement, and the next step. Avoid long lists of vague requirements.', 'author' => 'Luckyboss Team'],
            ['title' => 'Preparing for Your First Interview', 'category' => 'Interview Tips', 'short_description' => 'A practical checklist for confident job interviews.', 'content' => 'Review the role, prepare concise examples from your experience, test your meeting link, and prepare thoughtful questions for the interviewer.', 'author' => 'Luckyboss Team'],
            ['title' => 'Skills That Stand Out in Logistics', 'category' => 'Industry News', 'short_description' => 'The capabilities modern logistics teams value most.', 'content' => 'Safety awareness, inventory accuracy, communication, and comfort with operational systems are increasingly valuable across logistics roles.', 'author' => 'Luckyboss Team'],
            ['title' => 'Making Your Profile Searchable', 'category' => 'Resume Tips', 'short_description' => 'Small profile improvements that help employers find you.', 'content' => 'Use specific job titles, list your strongest skills, keep your location current, and explain measurable achievements in your work history.', 'author' => 'Luckyboss Team'],
            ['title' => 'Interview Feedback That Improves Hiring', 'category' => 'Employer Guides', 'short_description' => 'Turn interview notes into consistent hiring decisions.', 'content' => 'Record evidence against the role requirements, separate facts from impressions, and capture a clear recommendation while the conversation is fresh.', 'author' => 'Luckyboss Team'],
        ] as $post) {
            Blog::updateOrCreate(['slug' => str($post['title'])->slug()], $post + ['slug' => str($post['title'])->slug(), 'image_path' => 'images/lucky-boss-logo.png', 'is_published' => true, 'published_at' => now()->subDays(rand(1, 14))]);
        }
        Blog::whereNull('image_path')->update(['image_path' => 'images/lucky-boss-logo.png']);
    
        // Seed Comprehensive Admin Master Records
        $masterRecords = [
            'job-master-work-modes' => [
                ['name' => 'Remote', 'description' => '100% work from home / any location', 'payload' => ['sort_order' => 1, 'badge_color' => 'emerald']],
                ['name' => 'Hybrid', 'description' => 'Combination of in-office and remote work', 'payload' => ['sort_order' => 2, 'badge_color' => 'blue']],
                ['name' => 'On-site / In-Office', 'description' => 'Physical presence required at company office', 'payload' => ['sort_order' => 3, 'badge_color' => 'amber']],
                ['name' => 'Flexible', 'description' => 'Work mode decided mutually between employer and candidate', 'payload' => ['sort_order' => 4, 'badge_color' => 'purple']],
            ],
            'job-master-job-types' => [
                ['name' => 'Full-Time', 'description' => 'Standard full-time employment (40 hrs/week)', 'payload' => ['sort_order' => 1]],
                ['name' => 'Part-Time', 'description' => 'Part-time employment (less than 30 hrs/week)', 'payload' => ['sort_order' => 2]],
                ['name' => 'Contract / Fixed Term', 'description' => 'Fixed-duration contract role', 'payload' => ['sort_order' => 3]],
                ['name' => 'Freelance / Consultant', 'description' => 'Project-based independent contractor', 'payload' => ['sort_order' => 4]],
                ['name' => 'Internship', 'description' => 'Entry-level trainee or student internship', 'payload' => ['sort_order' => 5]],
                ['name' => 'Temporary', 'description' => 'Seasonal or short-term coverage position', 'payload' => ['sort_order' => 6]],
            ],
            'job-master-experience-levels' => [
                ['name' => 'Entry Level (0-2 years)', 'description' => 'Graduates or candidates with 0 to 2 years experience', 'payload' => ['min_years' => 0, 'max_years' => 2, 'sort_order' => 1]],
                ['name' => 'Mid Level (2-5 years)', 'description' => 'Experienced professionals with 2 to 5 years experience', 'payload' => ['min_years' => 2, 'max_years' => 5, 'sort_order' => 2]],
                ['name' => 'Senior Level (5-8 years)', 'description' => 'Senior individual contributors with 5 to 8 years experience', 'payload' => ['min_years' => 5, 'max_years' => 8, 'sort_order' => 3]],
                ['name' => 'Lead / Principal (8-12 years)', 'description' => 'Team leads, architects, and principal contributors', 'payload' => ['min_years' => 8, 'max_years' => 12, 'sort_order' => 4]],
                ['name' => 'Executive / Director (12+ years)', 'description' => 'Head of departments, Directors, VP, and C-level executives', 'payload' => ['min_years' => 12, 'max_years' => 30, 'sort_order' => 5]],
            ],
            'job-master-education-levels' => [
                ['name' => 'High School / Secondary', 'description' => 'Secondary school completion or equivalent', 'payload' => ['sort_order' => 1]],
                ['name' => "Bachelor's Degree (B.Tech, B.Sc, B.Com, B.A, BBA)", 'description' => 'Undergraduate university degree', 'payload' => ['sort_order' => 2]],
                ['name' => "Master's Degree (M.Tech, M.Sc, MBA, M.A, MCA)", 'description' => 'Postgraduate masters qualification', 'payload' => ['sort_order' => 3]],
                ['name' => 'Doctorate / Ph.D.', 'description' => 'Doctoral degree or research doctorate', 'payload' => ['sort_order' => 4]],
                ['name' => 'Professional Diploma / Certificate', 'description' => 'Specialized polytechnic or professional credential', 'payload' => ['sort_order' => 5]],
            ],
            'job-master-industries' => [
                ['name' => 'Information Technology & Software', 'description' => 'Software development, SaaS, cloud computing, AI, and cybersecurity', 'payload' => ['sort_order' => 1]],
                ['name' => 'Healthcare & Pharmaceuticals', 'description' => 'Hospitals, biotech, clinical research, and medical devices', 'payload' => ['sort_order' => 2]],
                ['name' => 'Banking & Financial Services (BFSI)', 'description' => 'Fintech, investment banking, insurance, and accounting', 'payload' => ['sort_order' => 3]],
                ['name' => 'Engineering & Construction', 'description' => 'Civil, mechanical, electrical engineering, and infrastructure', 'payload' => ['sort_order' => 4]],
                ['name' => 'Education & EdTech', 'description' => 'Universities, schools, online learning, and corporate training', 'payload' => ['sort_order' => 5]],
                ['name' => 'Retail, FMCG & E-Commerce', 'description' => 'Consumer goods, online marketplaces, and retail operations', 'payload' => ['sort_order' => 6]],
                ['name' => 'Logistics, Supply Chain & Shipping', 'description' => 'Freight forwarding, warehousing, and transportation', 'payload' => ['sort_order' => 7]],
                ['name' => 'Manufacturing & Automotive', 'description' => 'Industrial manufacturing, EV, and robotics', 'payload' => ['sort_order' => 8]],
                ['name' => 'Hospitality, Travel & Aviation', 'description' => 'Airlines, luxury hotels, tourism, and event management', 'payload' => ['sort_order' => 9]],
            ],
            'job-master-skills' => [
                ['name' => 'Laravel & PHP', 'description' => 'Modern PHP 8+, Laravel MVC, Blade, Livewire, and Eloquent', 'payload' => ['category' => 'Backend Development', 'sort_order' => 1]],
                ['name' => 'Python & Machine Learning', 'description' => 'Python 3, PyTorch, TensorFlow, Pandas, and FastApi', 'payload' => ['category' => 'Data & AI', 'sort_order' => 2]],
                ['name' => 'React & Next.js', 'description' => 'Frontend React.js, TypeScript, Next.js, and Redux', 'payload' => ['category' => 'Frontend Development', 'sort_order' => 3]],
                ['name' => 'Vue.js & Tailwind CSS', 'description' => 'Vue 3, Nuxt.js, Tailwind styling, and Alpine.js', 'payload' => ['category' => 'Frontend Development', 'sort_order' => 4]],
                ['name' => 'Cloud & DevOps (AWS / Docker)', 'description' => 'Amazon Web Services, Docker containers, Kubernetes, and CI/CD', 'payload' => ['category' => 'DevOps & Infrastructure', 'sort_order' => 5]],
                ['name' => 'SQL & Relational Databases', 'description' => 'MySQL, PostgreSQL, query optimization, and schema design', 'payload' => ['category' => 'Database', 'sort_order' => 6]],
                ['name' => 'UI / UX Product Design', 'description' => 'Figma, user research, wireframing, and design systems', 'payload' => ['category' => 'Design', 'sort_order' => 7]],
                ['name' => 'Digital Marketing & SEO', 'description' => 'Search engine optimization, Google Ads, and performance marketing', 'payload' => ['category' => 'Marketing', 'sort_order' => 8]],
            ],
            'job-master-shifts' => [
                ['name' => 'General / Day Shift', 'description' => '9:00 AM to 6:00 PM standard day schedule', 'payload' => ['hours' => '9 AM - 6 PM', 'sort_order' => 1]],
                ['name' => 'Night Shift (US / UK overlap)', 'description' => 'Overnight shift for international client coverage', 'payload' => ['hours' => '8 PM - 5 AM', 'sort_order' => 2]],
                ['name' => 'Rotational Shift (24/7 Operations)', 'description' => 'Alternating weekly morning, afternoon, and evening shifts', 'payload' => ['hours' => 'Rotational', 'sort_order' => 3]],
                ['name' => 'Flexible Shift Hours', 'description' => 'Core hours with flexible start and end times', 'payload' => ['hours' => 'Flexible', 'sort_order' => 4]],
            ],
            'job-master-certifications' => [
                ['name' => 'AWS Certified Solutions Architect', 'description' => 'Amazon Web Services cloud architecture certification', 'payload' => ['issuer' => 'Amazon Web Services', 'sort_order' => 1]],
                ['name' => 'Project Management Professional (PMP)', 'description' => 'Globally recognized PMI project management qualification', 'payload' => ['issuer' => 'PMI', 'sort_order' => 2]],
                ['name' => 'Certified Scrum Master (CSM)', 'description' => 'Agile and Scrum framework leadership credential', 'payload' => ['issuer' => 'Scrum Alliance', 'sort_order' => 3]],
                ['name' => 'Certified Information Systems Security Professional (CISSP)', 'description' => 'Premier cybersecurity information security credential', 'payload' => ['issuer' => '(ISC)²', 'sort_order' => 4]],
                ['name' => 'Google Cloud Professional Cloud Architect', 'description' => 'GCP infrastructure and architecture certification', 'payload' => ['issuer' => 'Google Cloud', 'sort_order' => 5]],
            ],
            'job-master-salary-types' => [
                ['name' => 'Annual Fixed Salary (CTC / LPA)', 'description' => 'Annual compensation package paid monthly', 'payload' => ['type' => 'annual', 'sort_order' => 1]],
                ['name' => 'Monthly Fixed Salary', 'description' => 'Standard monthly fixed salary', 'payload' => ['type' => 'monthly', 'sort_order' => 2]],
                ['name' => 'Hourly Contractor Rate', 'description' => 'Billed hourly based on timesheet hours', 'payload' => ['type' => 'hourly', 'sort_order' => 3]],
                ['name' => 'Commission & Performance Incentive Based', 'description' => 'Base salary plus performance-linked commission', 'payload' => ['type' => 'commission', 'sort_order' => 4]],
            ],
            'job-master-notice-periods' => [
                ['name' => 'Immediate / Available Now', 'description' => 'Can join immediately within 0 to 7 days', 'payload' => ['days' => 0, 'sort_order' => 1]],
                ['name' => '15 Days Notice', 'description' => 'Serving notice, available in 15 days', 'payload' => ['days' => 15, 'sort_order' => 2]],
                ['name' => '30 Days (1 Month)', 'description' => 'Standard 1-month resignation notice period', 'payload' => ['days' => 30, 'sort_order' => 3]],
                ['name' => '60 Days (2 Months)', 'description' => '2-month notice period', 'payload' => ['days' => 60, 'sort_order' => 4]],
                ['name' => '90 Days (3 Months)', 'description' => '3-month executive notice period', 'payload' => ['days' => 90, 'sort_order' => 5]],
            ],
            'job-master-visa-work-permit-types' => [
                ['name' => 'Citizen / National', 'description' => 'Full legal right to work without sponsorship', 'payload' => ['sort_order' => 1]],
                ['name' => 'Permanent Resident (PR)', 'description' => 'Permanent resident visa with unrestricted employment rights', 'payload' => ['sort_order' => 2]],
                ['name' => 'Employment Visa / Work Permit', 'description' => 'Valid sponsored work permit or employment visa', 'payload' => ['sort_order' => 3]],
                ['name' => 'Student Visa (Part-Time Permitted)', 'description' => 'Permitted up to 20 hours per week during study term', 'payload' => ['sort_order' => 4]],
                ['name' => 'Employer Sponsorship Required', 'description' => 'Candidate requires visa sponsorship to work legally', 'payload' => ['sort_order' => 5]],
            ],
            'settings-seo' => [
                ['name' => 'Global SEO Meta Title', 'description' => 'Default title displayed in search engines', 'payload' => ['value' => 'Luckyboss — AI-Powered Global Recruitment & Job Marketplace']],
                ['name' => 'Meta Description', 'description' => 'Summary snippet for Google search results', 'payload' => ['value' => 'Connect top tier talent with verified employers globally. Search thousands of vetted jobs and build your career with Luckyboss.']],
                ['name' => 'OpenGraph Social Share Image', 'description' => 'Preview image when links are shared on LinkedIn / Twitter', 'payload' => ['value' => '/images/lucky-boss-og-banner.png']],
            ],
            'settings-currency' => [
                ['name' => 'Base Operational Currency', 'description' => 'Platform primary accounting currency', 'payload' => ['code' => 'USD', 'symbol' => '$', 'format' => '$ 1,000.00']],
                ['name' => 'Supported Currencies', 'description' => 'Enabled currencies for subscriptions and payments', 'payload' => ['currencies' => ['USD', 'INR', 'EUR', 'GBP', 'AED', 'SGD']]],
            ],
            'settings-email-configuration' => [
                ['name' => 'Gmail SMTP Production Service', 'description' => 'Active platform transactional email provider', 'payload' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'from_email' => 'luckybossea@gmail.com', 'from_name' => 'Luckyboss Platform']],
            ],
            'settings-maintenance-mode' => [
                ['name' => 'Platform Maintenance Status', 'description' => 'Live maintenance mode switch and whitelist rules', 'payload' => ['is_active' => false, 'message' => 'Luckyboss is undergoing scheduled maintenance. We will be back shortly.', 'whitelisted_ips' => ['127.0.0.1', '::1']]],
            ],
            'settings-terms' => [
                ['name' => 'Terms of Service v2.1', 'description' => 'Active user terms and conditions agreement', 'payload' => ['last_updated' => '2026-08-01', 'effective_date' => '2026-08-01', 'status' => 'Published']],
            ],
            'settings-privacy' => [
                ['name' => 'Privacy & GDPR Policy v2.0', 'description' => 'Candidate and employer data protection compliance', 'payload' => ['gdpr_compliant' => true, 'data_retention_days' => 365, 'status' => 'Published']],
            ],
            'system-login-security' => [
                ['name' => 'Brute Force & Login Protection', 'description' => 'Rate limiting on failed auth attempts', 'payload' => ['max_attempts' => 5, 'lockout_minutes' => 15, 'status' => 'Enabled']],
            ],
            'system-password-policy' => [
                ['name' => 'Enterprise Password Policy', 'description' => 'Strength requirements for admin and employer accounts', 'payload' => ['min_length' => 8, 'require_uppercase' => true, 'require_numbers' => true, 'require_symbols' => true]],
            ],
            'system-session-settings' => [
                ['name' => 'Session Timeout & Concurrency', 'description' => 'Session idle timeout and multi-device rules', 'payload' => ['lifetime_minutes' => 120, 'expire_on_close' => false, 'driver' => 'database']],
            ],
            'system-ip-blocking' => [
                ['name' => 'IP Whitelist / Blacklist Policy', 'description' => 'Restricted CIDR blocks and threat mitigation', 'payload' => ['blacklist_count' => 0, 'status' => 'Active Monitoring']],
            ],
            'system-api-rate-limits' => [
                ['name' => 'Global API Gateway Throttling', 'description' => 'Rate limits per IP and API key', 'payload' => ['public_routes' => '60 req/min', 'auth_routes' => '120 req/min', 'resume_parser' => '20 req/min']],
            ],
            'system-import-export' => [
                ['name' => 'Batch Data Import/Export Engine', 'description' => 'Supported data formats and max upload limits', 'payload' => ['formats' => ['CSV', 'XLSX', 'JSON'], 'max_file_size_mb' => 25]],
            ],
            'system-system-logs' => [
                ['name' => 'Application Runtime Telemetry', 'description' => 'Logging channel and error reporting levels', 'payload' => ['log_level' => 'debug', 'channel' => 'daily', 'status' => 'Healthy']],
            ],
            'system-backup' => [
                ['name' => 'Automated Database Backups', 'description' => 'Scheduled SQLite & media file snapshot jobs', 'payload' => ['frequency' => 'Daily at 02:00 UTC', 'retention_days' => 30, 'storage' => 'local_and_cloud']],
            ],
            'mobile-apps-job-seeker-app-settings' => [
                ['name' => 'Luckyboss Candidate Mobile App (Flutter)', 'description' => 'Production mobile app configuration for Job Seekers', 'payload' => ['version' => '2.4.0', 'build_number' => 124, 'min_supported_version' => '2.0.0', 'force_update' => false, 'play_store_url' => 'https://play.google.com/store/apps/details?id=com.luckyboss.seeker', 'app_store_url' => 'https://apps.apple.com/app/luckyboss-jobs/id123456789']],
            ],
            'mobile-apps-employer-app-settings' => [
                ['name' => 'Luckyboss Recruiter Mobile App (Flutter)', 'description' => 'Production mobile app configuration for Employers', 'payload' => ['version' => '1.8.2', 'build_number' => 82, 'min_supported_version' => '1.5.0', 'force_update' => false, 'play_store_url' => 'https://play.google.com/store/apps/details?id=com.luckyboss.recruiter', 'app_store_url' => 'https://apps.apple.com/app/luckyboss-recruiter/id987654321']],
            ],
        ];

        foreach ($masterRecords as $module => $items) {
            foreach ($items as $item) {
                AdminRecord::updateOrCreate(
                    ['module' => $module, 'name' => $item['name']],
                    [
                        'slug' => str($item['name'])->slug(),
                        'description' => $item['description'],
                        'payload' => $item['payload'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
