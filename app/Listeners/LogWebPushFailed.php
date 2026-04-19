<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Events\NotificationFailed;

class LogWebPushFailed
{
    public function handle(NotificationFailed $event): void
    {
        Log::warning('Web Push fallido.', [
            'endpoint' => $event->subscription->endpoint,
            'subscribable_id' => $event->subscription->subscribable_id,
            'subscribable_type' => $event->subscription->subscribable_type,
            'reason' => $event->report->getReason(),
            'expired' => $event->report->isSubscriptionExpired(),
        ]);
    }
}