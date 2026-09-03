<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    public function for(?User $user): array
    {
        if (! $user) return ['area' => 'public', 'items' => [['label'=>'Home','url'=>route('home')],['label'=>'Find Jobs','url'=>route('jobs.index')],['label'=>'Job Categories','url'=>route('categories.index')],['label'=>'Employers','url'=>route('employers.public')],['label'=>'Job Seekers','url'=>route('seekers.public')],['label'=>'Specializations','url'=>route('specializations.index')],['label'=>'Blog','url'=>route('blogs.index')],['label'=>'About Us','url'=>route('page.show','about-us')],['label'=>'Contact Us','url'=>route('page.show','faq')]]];

        if ($user->hasRole('job-seeker')) {
            $items = [['label'=>'Dashboard','url'=>route('seeker.dashboard')],['label'=>'My Profile','url'=>route('seeker.dashboard').'#profile'],['label'=>'My Resume','url'=>route('seeker.dashboard').'#resume'],['label'=>'Find Jobs','url'=>route('jobs.index')],['label'=>'My Applications','url'=>route('seeker.dashboard').'#applications'],['label'=>'Recommended Jobs','url'=>route('seeker.dashboard').'#recommended'],['label'=>'Saved Jobs','url'=>route('seeker.dashboard').'#saved'],['label'=>'Job Alerts','url'=>route('seeker.dashboard').'#alerts'],['label'=>'Interviews','url'=>route('seeker.dashboard').'#interviews'],['label'=>'Offers','url'=>route('seeker.dashboard').'#offers'],['label'=>'Notifications','url'=>route('seeker.dashboard').'#notifications'],['label'=>'Messages','url'=>route('seeker.dashboard').'#messages'],['label'=>'Help & Support','url'=>route('seeker.dashboard').'#support'],['label'=>'Settings','url'=>route('seeker.dashboard').'#settings']];
            if (app(FeatureFlagService::class)->enabled('external_jobs_enabled')) $items[]=['label'=>'External Jobs','url'=>route('seeker.dashboard').'#external-jobs'];
            if (app(FeatureFlagService::class)->enabled('candidate_monetization_enabled') || $user->payments()->exists()) $items[]=['label'=>'Purchase History','url'=>route('seeker.dashboard').'#purchases'];
            return ['area'=>'job-seeker','items'=>$items];
        }

        if ($user->hasRole('employer')) {
            $company=$user->companies()->first(); $entitlements=$company?->subscriptions()->where('status','active')->whereDate('expires_at','>=',today())->latest('expires_at')->value('entitlements')??[];
            $items=[['label'=>'Dashboard','url'=>route('employer.dashboard')],['label'=>'Jobs','url'=>route('employer.jobs.index')],['label'=>'Candidates','url'=>route('employer.jobs.index')],['label'=>'Recruitment','url'=>route('employer.jobs.index')],['label'=>'Interviews','url'=>route('employer.dashboard').'#interviews'],['label'=>'Offers','url'=>route('employer.dashboard').'#offers'],['label'=>'Messages','url'=>route('employer.dashboard').'#messages'],['label'=>'Reports','url'=>route('employer.dashboard').'#reports'],['label'=>'Team & Users','url'=>route('employer.dashboard').'#team'],['label'=>'Subscription','url'=>route('employer.dashboard').'#subscription'],['label'=>'Billing','url'=>route('employer.dashboard').'#billing'],['label'=>'Notifications','url'=>route('employer.dashboard').'#notifications'],['label'=>'Company Profile','url'=>route('employer.dashboard').'#profile'],['label'=>'Settings','url'=>route('employer.dashboard').'#settings'],['label'=>'Help & Support','url'=>route('employer.dashboard').'#support']];
            if (data_get($entitlements,'candidate_views',0)!==0) $items[]=['label'=>'Candidate Search','url'=>route('employer.dashboard').'#candidate-search'];
            if (data_get($entitlements,'ai_matching',false)&&(app(FeatureFlagService::class)->enabled('platform_ai_enabled')||app(FeatureFlagService::class)->enabled('employer_byoai_enabled'))) $items[]=['label'=>'AI Tools','url'=>route('employer.dashboard').'#ai-tools'];
            return ['area'=>'employer','items'=>$items];
        }
        return ['area'=>'admin','items'=>[['label'=>'Dashboard','url'=>route('admin.dashboard')]]];
    }
}