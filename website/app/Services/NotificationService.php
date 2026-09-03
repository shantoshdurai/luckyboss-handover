<?php
namespace App\Services;
use App\Jobs\DeliverPortalNotification;
use App\Models\PlatformNotification;
use App\Models\User;
class NotificationService { public function send(User $user, string $type, string $title, string $body, array $data = [], ?string $sound = null): PlatformNotification { $notification = PlatformNotification::create(compact('type','title','body','data','sound') + ['user_id'=>$user->id]); DeliverPortalNotification::dispatch($notification); return $notification; } }