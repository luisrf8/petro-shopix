<?php

namespace App\Events;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryAssignmentUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(private SalesOrder $order, private User $actor)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.delivery-ops.' . (int) $this->order->tenant_id)];
    }

    public function broadcastAs(): string
    {
        return 'delivery.assignment.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => (int) $this->order->tenant_id,
            'order_id' => (int) $this->order->id,
            'delivery_assigned_user_id' => (int) ($this->order->delivery_assigned_user_id ?? 0),
            'delivery_assigned_user_name' => trim((string) ($this->order->assignedDeliveryUser->name ?? '')),
            'actor_user_id' => (int) ($this->actor->id ?? 0),
            'actor_user_name' => trim((string) ($this->actor->name ?? '')),
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}