<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $page): View
    {
        $pages = [
            'about-us' => ['About Luckyboss', 'Your growth partner in the hiring journey.', 'Luckyboss connects employers with capable people across Singapore, Malaysia, India and beyond. We combine recruitment expertise with practical technology to make each hiring journey clearer and faster.'],
            'faq' => ['Frequently Asked Questions', 'Answers for candidates and employers.', 'Create a job seeker account to search and apply for roles, or register your company to begin your hiring journey. Our team will guide you through account verification and the next steps.'],
            'terms-and-conditions' => ['Terms & Conditions', 'The terms that govern use of Luckyboss Portal.', 'By using Luckyboss Portal, you agree to provide accurate information, keep account access secure, and use the platform only for lawful recruitment and employment purposes.'],
            'privacy-policy' => ['Privacy Policy', 'How Luckyboss handles personal information.', 'We collect and process account, profile, application, and company information to operate the recruitment platform. We use appropriate safeguards and only share information necessary for legitimate recruitment workflows.'],
            'refund-policy' => ['Refund Policy', 'Our approach to payments and refunds.', 'Subscription, job promotion, and other paid-service refunds are assessed according to the applicable package terms and the status of services already delivered. Contact support with your payment reference for assistance.'],
        ];

        abort_unless(isset($pages[$page]), 404);

        return view('pages.show', ['page' => $pages[$page]]);
    }
}