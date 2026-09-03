<?php

namespace App\Services;

use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    /**
     * Notification channel mapping matching PDF specification
     */
    protected array $channelMap = [
        'job_match' => ['channel_id' => 'luckyboss_jobs', 'sound' => 'job_match', 'priority' => 'high'],
        'application_update' => ['channel_id' => 'luckyboss_applications', 'sound' => 'application_update', 'priority' => 'high'],
        'interview_alert' => ['channel_id' => 'luckyboss_interviews', 'sound' => 'interview_alert', 'priority' => 'max'],
        'offer_alert' => ['channel_id' => 'luckyboss_offers', 'sound' => 'offer_alert', 'priority' => 'max'],
        'applicant_alert' => ['channel_id' => 'luckyboss_applicants', 'sound' => 'applicant_alert', 'priority' => 'high'],
        'payment_alert' => ['channel_id' => 'luckyboss_payments', 'sound' => 'payment_alert', 'priority' => 'high'],
        'system_alert' => ['channel_id' => 'luckyboss_system', 'sound' => 'system_alert', 'priority' => 'high'],
        'approval_alert' => ['channel_id' => 'luckyboss_approvals', 'sound' => 'approval_alert', 'priority' => 'high'],
    ];

    /**
     * Dispatch an FCM Push Notification to a user's mobile device
     */
    public function sendToUser(User $user, PlatformNotification $notification): bool
    {
        $token = $user->fcm_token;
        if (!$token) {
            return false;
        }

        $type = $notification->type ?? 'system_alert';
        $meta = $this->channelMap[$type] ?? ['channel_id' => 'luckyboss_default', 'sound' => 'default', 'priority' => 'high'];

        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->body,
                'sound' => $meta['sound'],
                'android_channel_id' => $meta['channel_id'],
            ],
            'data' => array_merge($notification->data ?? [], [
                'notification_id' => (string) $notification->id,
                'type' => $type,
                'sound' => $meta['sound'],
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]),
            'priority' => $meta['priority'],
        ];

        try {
            $apiKey = config('services.firebase.api_key');
            if (!$apiKey) {
                return false;
            }

            // Dispatch to FCM Legacy/HTTP endpoint with server key or project API key
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info("FCM push dispatched to user #{$user->id} for {$type}");
                return true;
            } else {
                Log::warning("FCM dispatch failed for user #{$user->id}: " . $response->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }
}
