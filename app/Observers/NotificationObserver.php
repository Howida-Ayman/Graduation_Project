<?php

namespace App\Observers;

use App\Models\DatabaseNotification;
use App\Events\NewNotificationEvent;

class NotificationObserver
{
    /**
     * يتم تشغيلها تلقائياً عند إنشاء أي إشعار جديد
     */
public function created(DatabaseNotification $notification): void
{
    \Log::info('Notification Observer Triggered');

    event(new NewNotificationEvent(
        $notification,
        $notification->notifiable_id
    ));
}
}