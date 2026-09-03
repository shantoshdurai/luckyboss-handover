<?php

return [
    'providers' => [
        'openai' => ['name' => 'OpenAI', 'purpose' => 'AI matching, resume support, email and offer drafts', 'fields' => ['API Key', 'Model']],
        'stripe' => ['name' => 'Stripe', 'purpose' => 'Employer subscriptions and candidate payments', 'fields' => ['Secret Key', 'Webhook Signing Secret']],
        'razorpay' => ['name' => 'Razorpay', 'purpose' => 'Regional employer and candidate payments', 'fields' => ['Key Secret', 'Webhook Signing Secret']],
        'whatsapp' => ['name' => 'WhatsApp Cloud API', 'purpose' => 'WhatsApp notifications and reminders', 'fields' => ['Access Token', 'Phone Number ID']],
        'fcm' => ['name' => 'Firebase Cloud Messaging', 'purpose' => 'Flutter push notifications and notification sounds', 'fields' => ['Server Credential', 'Project ID']],
        'email' => ['name' => 'Email Provider', 'purpose' => 'Queued portal email delivery', 'fields' => ['API Key / SMTP Password', 'Sender Address']],
        'resume-parser' => ['name' => 'Resume Parser', 'purpose' => 'Resume parsing into candidate profile data', 'fields' => ['API Key', 'Endpoint']],
        'vector-db' => ['name' => 'Vector Database', 'purpose' => 'Optional semantic candidate and job matching', 'fields' => ['API Key', 'Index / Collection']],
        'google-calendar' => ['name' => 'Google Calendar', 'purpose' => 'Interview calendar and meeting integration', 'fields' => ['OAuth Client Secret', 'OAuth Client ID']],
    ],
];