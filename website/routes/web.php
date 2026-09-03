<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\CommandCenterController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\EmployerNoteController;
use App\Http\Controllers\Admin\EmployerOperationsController;
use App\Http\Controllers\Admin\CandidateOperationsController;
use App\Http\Controllers\Admin\JobController as AdminJobController;
use App\Http\Controllers\Admin\RecruitmentController as AdminRecruitmentController;
use App\Http\Controllers\Admin\ExternalDataController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\AiApiController;
use App\Http\Controllers\Admin\InterviewController as AdminInterviewController;
use App\Http\Controllers\Admin\CommunicationController as AdminCommunicationController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ControlCenterController as AdminControlCenterController;
use App\Http\Controllers\Admin\RecordController;
use App\Http\Controllers\Admin\AdminOperationsController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\Employer\PortalController as EmployerPortalController;
use App\Http\Controllers\Employer\RecruitmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\Seeker\DashboardController as SeekerDashboardController;
use App\Http\Controllers\Seeker\OfferController;
use App\Http\Controllers\Seeker\ProfileController as SeekerProfileController;
use App\Http\Controllers\Seeker\SavedJobController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jobs', [PublicPortalController::class,'jobs'])->name('jobs.index');
Route::get('/jobs/{job}', [PublicPortalController::class, 'show'])->whereNumber('job')->name('jobs.show');

// Discovery for Google for Jobs. Generated on request so a closed vacancy is
// never left in it — a sitemap full of dead URLs costs crawl budget.
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');
Route::get('/jobs/suggestions', [PublicPortalController::class,'suggestions'])->name('jobs.suggestions');
Route::get('/job-categories', [PublicPortalController::class,'categories'])->name('categories.index');
Route::get('/specializations', fn() => redirect()->route('categories.index'))->name('specializations.index');
Route::get('/employers', [PublicPortalController::class,'employers'])->name('employers.public');
Route::get('/job-seekers', [PublicPortalController::class,'seekers'])->name('seekers.public');
Route::get('/contact', [PublicPortalController::class,'contact'])->name('contact.public');
Route::get('/blog', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blog/{blog:slug}', [BlogController::class, 'show'])->name('blogs.show');
Route::get('/pages/{page}', [PageController::class, 'show'])->where('page', 'about-us|faq|terms-and-conditions|privacy-policy|refund-policy')->name('page.show');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::get('/admin', [DashboardController::class, '__invoke'])->name('admin.dashboard');
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/notifications/feed', function () {
        $notifications = \App\Models\PlatformNotification::where(function ($q) {
                $q->where('user_id', auth()->id())->orWhereNull('user_id');
            })
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type ?? 'system_alert',
                    'title' => $n->title,
                    'body' => $n->body,
                    'time' => $n->created_at ? $n->created_at->diffForHumans() : 'Just now',
                    'unread' => is_null($n->read_at),
                ];
            });
        $unreadCount = $notifications->where('unread', true)->count();
        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    })->name('notifications.feed');

    // Marks every notification visible to this user as read. Kept from our tree
    // when sir's web layer was merged in — the mobile apps and the portal bell
    // both call it, and his copy predates it.
    Route::post('/notifications/clear-all', function () {
        \App\Models\PlatformNotification::where(function ($q) {
                $q->where('user_id', auth()->id())->orWhereNull('user_id');
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['status' => 'success', 'message' => 'Notifications cleared for user.']);
    })->name('notifications.clear-all');

    Route::post('/notifications/mark-all-read', function () {
        \App\Models\PlatformNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['status' => 'success']);
    })->name('notifications.mark-all-read');
});

// Bare /register kept from our tree: several links and the apps point at it.
Route::get('/register', fn () => redirect()->route('register.seeker'))->name('register');
Route::get('/register/job-seeker', [AuthController::class, 'showCandidateRegister'])->name('register.seeker');
Route::post('/register/job-seeker', [AuthController::class, 'registerCandidate'])->name('register.seeker.store');
Route::get('/register/employer', [AuthController::class, 'showEmployerRegister'])->name('register.employer');
Route::post('/register/employer', [AuthController::class, 'registerEmployer'])->name('register.employer.store');

Route::middleware('auth')->group(function (): void {
	Route::get('/admin/site-settings', [SiteSettingsController::class,'edit'])->name('admin.site-settings.edit');
	Route::put('/admin/site-settings', [SiteSettingsController::class,'update'])
		->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
		->name('admin.site-settings.update');
	Route::get('/admin/command/{section}/{view?}', [CommandCenterController::class, 'show'])->name('admin.command.show');
	Route::get('/admin/companies', [CompanyController::class, 'index'])->name('admin.companies.index');
	Route::get('/admin/companies/{company}/edit', [CompanyController::class, 'edit'])->name('admin.companies.edit');
	Route::put('/admin/companies/{company}', [CompanyController::class, 'update'])->name('admin.companies.update');
	Route::post('/admin/companies/{company}/status/{status}', [CompanyController::class, 'status'])->name('admin.companies.status');
	Route::delete('/admin/companies/{company}', [CompanyController::class, 'destroy'])->name('admin.companies.destroy');
	Route::resource('/admin/employer-notes', EmployerNoteController::class)->except('show')->parameters(['employer-notes' => 'note'])->names('admin.employer-notes');
	Route::get('/admin/employer-users', [EmployerOperationsController::class, 'users'])->name('admin.employer-users.index');
	Route::post('/admin/employer-users/{user}/companies/{company}/toggle', [EmployerOperationsController::class, 'toggleUser'])->name('admin.employer-users.toggle');
	Route::get('/admin/employer-documents', [EmployerOperationsController::class, 'documents'])->name('admin.employer-documents.index');
	Route::post('/admin/employer-documents', [EmployerOperationsController::class, 'storeDocument'])->name('admin.employer-documents.store');
	Route::put('/admin/employer-documents/{document}', [EmployerOperationsController::class, 'updateDocument'])->name('admin.employer-documents.update');
	Route::delete('/admin/employer-documents/{document}', [EmployerOperationsController::class, 'destroyDocument'])->name('admin.employer-documents.destroy');
	Route::get('/admin/employer-activity', [EmployerOperationsController::class, 'activity'])->name('admin.employer-activity.index');
	Route::get('/admin/candidates', [CandidateOperationsController::class, 'index'])->name('admin.candidates.index');
	Route::put('/admin/candidates/{candidate}', [CandidateOperationsController::class, 'updateCandidate'])->name('admin.candidates.update');
	Route::post('/admin/candidates/{candidate}/toggle', [CandidateOperationsController::class, 'toggleCandidate'])->name('admin.candidates.toggle');
	Route::delete('/admin/candidates/{candidate}', [CandidateOperationsController::class, 'destroyCandidate'])->name('admin.candidates.destroy');
	Route::post('/admin/candidate-skills', [CandidateOperationsController::class, 'storeSkill'])->name('admin.candidate-skills.store');
	Route::delete('/admin/candidate-skills/{skill}', [CandidateOperationsController::class, 'destroySkill'])->name('admin.candidate-skills.destroy');
	Route::delete('/admin/candidate-resumes/{resume}', [CandidateOperationsController::class, 'destroyResume'])->name('admin.candidate-resumes.destroy');
	Route::put('/admin/candidate-applications/{application}', [CandidateOperationsController::class, 'updateApplication'])->name('admin.candidate-applications.update');
	Route::put('/admin/candidate-purchases/{payment}', [CandidateOperationsController::class, 'updatePurchase'])->name('admin.candidate-purchases.update');
	Route::post('/admin/candidate-notes', [CandidateOperationsController::class, 'storeNote'])->name('admin.candidate-notes.store');
	Route::put('/admin/candidate-notes/{note}', [CandidateOperationsController::class, 'updateNote'])->name('admin.candidate-notes.update');
	Route::delete('/admin/candidate-notes/{note}', [CandidateOperationsController::class, 'destroyNote'])->name('admin.candidate-notes.destroy');
	Route::get('/admin/jobs', [AdminJobController::class, 'index'])->name('admin.jobs.index');
	Route::get('/admin/jobs/create', [AdminJobController::class, 'create'])->name('admin.jobs.create');
	Route::post('/admin/jobs', [AdminJobController::class, 'store'])->name('admin.jobs.store');
	Route::get('/admin/jobs/{job}/edit', [AdminJobController::class, 'edit'])->name('admin.jobs.edit');
	Route::put('/admin/jobs/{job}', [AdminJobController::class, 'update'])->name('admin.jobs.update');
	Route::post('/admin/jobs/{job}/status/{status}', [AdminJobController::class, 'status'])->name('admin.jobs.status');
	Route::post('/admin/jobs/{job}/flag/{flag}', [AdminJobController::class, 'flag'])->name('admin.jobs.flag');
	Route::delete('/admin/jobs/{job}', [AdminJobController::class, 'destroy'])->name('admin.jobs.destroy');
	Route::get('/admin/recruitment', [AdminRecruitmentController::class, 'index'])->name('admin.recruitment.index');
	Route::post('/admin/recruitment/{application}/status', [AdminRecruitmentController::class, 'status'])->name('admin.recruitment.status');
	Route::post('/admin/recruitment/{application}/interview', [AdminRecruitmentController::class, 'scheduleInterview'])->name('admin.recruitment.interview');
	Route::post('/admin/recruitment/{application}/offer', [AdminRecruitmentController::class, 'createOffer'])->name('admin.recruitment.offer');
	Route::delete('/admin/recruitment/{application}', [AdminRecruitmentController::class, 'destroy'])->name('admin.recruitment.destroy');
	Route::get('/admin/external-data', [ExternalDataController::class, 'index'])->name('admin.external-data.index');
	Route::post('/admin/external-data/sources', [ExternalDataController::class, 'storeSource'])->name('admin.external-data.sources.store');
	Route::put('/admin/external-data/sources/{source}', [ExternalDataController::class, 'updateSource'])->name('admin.external-data.sources.update');
	Route::delete('/admin/external-data/sources/{source}', [ExternalDataController::class, 'destroySource'])->name('admin.external-data.sources.destroy');
	Route::put('/admin/external-data/batches/{batch}', [ExternalDataController::class, 'updateBatch'])->name('admin.external-data.batches.update');
	Route::delete('/admin/external-data/batches/{batch}', [ExternalDataController::class, 'destroyBatch'])->name('admin.external-data.batches.destroy');
	Route::get('/admin/subscriptions', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
	Route::post('/admin/subscriptions/assign', [AdminSubscriptionController::class, 'assign'])->name('admin.subscriptions.assign');
	Route::put('/admin/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update'])->name('admin.subscriptions.update');
	Route::delete('/admin/subscriptions/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('admin.subscriptions.destroy');
	Route::get('/admin/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
	Route::put('/admin/payments/{payment}', [AdminPaymentController::class, 'update'])->name('admin.payments.update');
	Route::delete('/admin/payments/{payment}', [AdminPaymentController::class, 'destroy'])->name('admin.payments.destroy');
	Route::put('/admin/invoices/{invoice}', [AdminPaymentController::class, 'updateInvoice'])->name('admin.invoices.update');
	Route::delete('/admin/invoices/{invoice}', [AdminPaymentController::class, 'destroyInvoice'])->name('admin.invoices.destroy');
	Route::get('/admin/ai-api', [AiApiController::class, 'index'])->name('admin.ai-api.index');
	Route::put('/admin/ai-api/flags/{flag}', [AiApiController::class, 'updateFlag'])->name('admin.ai-api.flags.update');
	Route::put('/admin/ai-api/integrations/{integration}', [AiApiController::class, 'updateIntegration'])->name('admin.ai-api.integrations.update');
	Route::delete('/admin/ai-api/integrations/{integration}/error', [AiApiController::class, 'clearError'])->name('admin.ai-api.integrations.error.clear');
	Route::get('/admin/interviews', [AdminInterviewController::class, 'index'])->name('admin.interviews.index');
	Route::put('/admin/interviews/{interview}', [AdminInterviewController::class, 'update'])->name('admin.interviews.update');
	Route::delete('/admin/interviews/{interview}', [AdminInterviewController::class, 'destroy'])->name('admin.interviews.destroy');
	Route::post('/admin/interviews/modes', [AdminInterviewController::class, 'storeMode'])->name('admin.interviews.modes.store');
	Route::post('/admin/interviews/calendar-connections', [AdminInterviewController::class, 'storeConnection'])->name('admin.interviews.connections.store');
	Route::get('/admin/communication', [AdminCommunicationController::class, 'index'])->name('admin.communication.index');
	Route::post('/admin/communication/templates', [AdminCommunicationController::class, 'storeTemplate'])->name('admin.communication.templates.store');
	Route::put('/admin/communication/templates/{template}', [AdminCommunicationController::class, 'updateTemplate'])->name('admin.communication.templates.update');
	Route::delete('/admin/communication/templates/{template}', [AdminCommunicationController::class, 'destroyTemplate'])->name('admin.communication.templates.destroy');
	Route::delete('/admin/communication/logs/{log}', [AdminCommunicationController::class, 'destroyLog'])->name('admin.communication.logs.destroy');
	Route::get('/admin/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
	Route::post('/admin/notifications/sounds', [AdminNotificationController::class, 'storeSound'])->name('admin.notifications.sounds.store');
	Route::delete('/admin/notifications/sounds/{sound}', [AdminNotificationController::class, 'destroySound'])->name('admin.notifications.sounds.destroy');
	Route::delete('/admin/notifications/{notification}', [AdminNotificationController::class, 'destroy'])->name('admin.notifications.destroy');
	Route::get('/admin/cms', [CmsController::class, 'index'])->name('admin.cms.index');
	Route::post('/admin/cms/records', [CmsController::class, 'storeRecord'])->name('admin.cms.records.store');
	Route::delete('/admin/cms/records/{record}', [CmsController::class, 'destroyRecord'])->name('admin.cms.records.destroy');
	Route::get('/admin/support-center', [AdminSupportController::class, 'index'])->name('admin.support-center.index');
	Route::put('/admin/support-center/{ticket}', [AdminSupportController::class, 'update'])->name('admin.support-center.update');
	Route::delete('/admin/support-center/{ticket}', [AdminSupportController::class, 'destroy'])->name('admin.support-center.destroy');
	Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
	Route::get('/admin/control-center/{section}/{view}', [AdminControlCenterController::class, 'index'])->name('admin.control-center.index');
	Route::post('/admin/control-center/records', [AdminControlCenterController::class, 'storeRecord'])->name('admin.control-center.records.store');
	Route::get('/admin/records/{module}', [RecordController::class,'index'])->name('admin.records.index');
	Route::get('/admin/records/{module}/create', [RecordController::class,'create'])->name('admin.records.create');
	Route::post('/admin/records/{module}', [RecordController::class,'store'])->name('admin.records.store');
	Route::get('/admin/records/{module}/{record}/edit', [RecordController::class,'edit'])->name('admin.records.edit');
	Route::put('/admin/records/{module}/{record}', [RecordController::class,'update'])->name('admin.records.update');
	Route::delete('/admin/records/{module}/{record}', [RecordController::class,'destroy'])->name('admin.records.destroy');
	Route::get('/admin/support', [AdminOperationsController::class,'support'])->name('admin.support');
	Route::put('/admin/support/{ticket}', [AdminOperationsController::class,'updateSupport'])->name('admin.support.update');
	Route::get('/admin/external-sources', [AdminOperationsController::class,'sources'])->name('admin.sources');
	Route::post('/admin/external-sources', [AdminOperationsController::class,'storeSource'])->name('admin.sources.store');
	Route::get('/admin/invoices', [AdminOperationsController::class,'invoices'])->name('admin.invoices');
	Route::get('/admin/export/{type}', [AdminOperationsController::class,'export'])->name('admin.export');
	Route::resource('admin/blogs', AdminBlogController::class)->except('show')->names('admin.blogs');
	Route::get('/admin/operations/{area}', [OperationsController::class,'index'])->name('admin.operations.index');
	Route::get('/admin/operations/{area}/create', [OperationsController::class,'create'])->name('admin.operations.create');
	Route::post('/admin/operations/{area}', [OperationsController::class,'store'])->name('admin.operations.store');
	Route::get('/admin/operations/{area}/{record}/edit', [OperationsController::class,'edit'])->name('admin.operations.edit');
	Route::put('/admin/operations/{area}/{record}', [OperationsController::class,'update'])->name('admin.operations.update');
	Route::delete('/admin/operations/{area}/{record}', [OperationsController::class,'destroy'])->name('admin.operations.destroy');
	Route::prefix('admin/masters')->name('admin.masters.')->group(function (): void {
		Route::get('/{master}', [MasterController::class, 'index'])->name('index');
		Route::get('/{master}/create', [MasterController::class, 'create'])->name('create');
		Route::post('/{master}', [MasterController::class, 'store'])->name('store');
		Route::get('/{master}/{record}/edit', [MasterController::class, 'edit'])->name('edit');
		Route::put('/{master}/{record}', [MasterController::class, 'update'])->name('update');
		Route::delete('/{master}/{record}', [MasterController::class, 'destroy'])->name('destroy');
	});
	Route::get('/employer', EmployerDashboardController::class)->name('employer.dashboard');
	Route::get('/employer/portal/{section}', [EmployerPortalController::class, 'index'])->name('employer.portal');
	Route::post('/employer/portal/{section}', [EmployerPortalController::class, 'store'])->name('employer.portal.store');
	Route::put('/employer/portal-records/{record}', [EmployerPortalController::class, 'update'])->name('employer.portal.update');
	Route::delete('/employer/portal-records/{record}', [EmployerPortalController::class, 'destroy'])->name('employer.portal.destroy');
	Route::put('/employer/company-profile', [EmployerPortalController::class, 'updateProfile'])
		->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
		->name('employer.company-profile.update');
	Route::put('/employer/ai-configuration', [EmployerPortalController::class, 'updateAiConfiguration'])->name('employer.ai-configuration.update');
	Route::post('/employer/ai-configuration/test', [EmployerPortalController::class, 'testAiConfiguration'])->name('employer.ai-configuration.test');
	Route::delete('/employer/ai-configuration', [EmployerPortalController::class, 'removeAiConfiguration'])->name('employer.ai-configuration.remove');
	Route::resource('employer/jobs', EmployerJobController::class)->except(['show'])->names('employer.jobs');
	Route::get('/employer/jobs/{job}/applicants', [RecruitmentController::class,'show'])->name('employer.jobs.applicants');
	Route::post('/employer/jobs/{job}/applications/{application}/status', [RecruitmentController::class,'status'])->name('employer.applications.status');
	Route::post('/employer/jobs/{job}/applications/{application}/interview', [RecruitmentController::class,'interview'])->name('employer.applications.interview');
	Route::post('/employer/jobs/{job}/applications/{application}/offer', [RecruitmentController::class,'offer'])->name('employer.applications.offer');
	Route::get('/job-seeker', SeekerDashboardController::class)->name('seeker.dashboard');
	Route::post('/job-seeker/jobs/{job}/apply', [SeekerDashboardController::class, 'apply'])->name('seeker.jobs.apply');
	Route::delete('/job-seeker/applications/{application}/withdraw', [SeekerDashboardController::class, 'withdraw'])->name('seeker.applications.withdraw');
	Route::get('/job-seeker/profile', [SeekerProfileController::class, 'edit'])->name('seeker.profile.edit');
	Route::put('/job-seeker/profile', [SeekerProfileController::class, 'update'])->name('seeker.profile.update');
	Route::post('/job-seeker/resume/parse', [SeekerProfileController::class, 'parseResume'])->name('seeker.resume.parse');
	Route::post('/job-seeker/jobs/{job}/save', [SavedJobController::class, 'toggle'])->name('seeker.jobs.save');
	Route::post('/job-seeker/offers/{offer}/{response}', [OfferController::class,'respond'])->name('seeker.offers.respond');
});
