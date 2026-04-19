<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Events\NotificationSent;

class LogWebPushSent
{
    public function handle(NotificationSent $event): void
    {
        Log::info('Web Push enviado correctamente.', [
            'endpoint' => $event->subscription->endpoint,
            'subscribable_id' => $event->subscription->subscribable_id,
            'subscribable_type' => $event->subscription->subscribable_type,
        ]);
    }
}