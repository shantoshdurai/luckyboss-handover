<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\PlatformNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverPortalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PlatformNotification $notification) {}

    public function handle(FirebaseNotificationService $firebase): void
    {
        // 1. Log in-app notification
        CommunicationLog::create([
            'recipient_id' => $this->notification->user_id,
            'channel' => 'in-app',
            'status' => 'sent',
            'subject' => $this->notification->title,
            'body' => $this->notification->body,
            'sent_at' => now(),
        ]);

        // 2. Dispatch Mobile Push via Firebase FCM if user has device token
        $user = $this->notification->user;
        if ($user && $user->fcm_token) {
            $firebase->sendToUser($user, $this->notification);
        }
    }
}