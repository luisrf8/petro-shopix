<?php

namespace App\Notifications;

use App\Models\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WorkflowStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => (string) ($this->payload['title'] ?? 'Notificación'),
            'message' => (string) ($this->payload['message'] ?? ''),
            'type' => (string) ($this->payload['type'] ?? 'info'),
            'tenant_id' => $this->payload['tenant_id'] ?? null,
            'order_id' => $this->payload['order_id'] ?? null,
            'payment_id' => $this->payload['payment_id'] ?? null,
            'action' => (string) ($this->payload['action'] ?? ''),
            'meta' => $this->payload['meta'] ?? [],
            'target_url' => $this->resolveTargetUrl($notifiable),
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $targetUrl = $this->resolveTargetUrl($notifiable) ?? url('/');

        return (new WebPushMessage)
            ->title((string) ($this->payload['title'] ?? 'Notificación'))
            ->body((string) ($this->payload['message'] ?? 'Tienes una nueva notificación.'))
            ->icon(url('/assets/img/shopix5.png'))
            ->badge(url('/assets/img/shopix5.png'))
            ->tag('shopix-notification-' . ($this->payload['order_id'] ?? $this->payload['payment_id'] ?? 'general'))
            ->data([
                'url' => $targetUrl,
                'order_id' => $this->payload['order_id'] ?? null,
                'payment_id' => $this->payload['payment_id'] ?? null,
                'type' => (string) ($this->payload['type'] ?? 'info'),
            ])
            ->action('Abrir', 'open_url')
            ->options([
                'TTL' => 86400,
            ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = (string) ($this->payload['title'] ?? 'Notificación');
        $message = (string) ($this->payload['message'] ?? 'Tienes una nueva notificación.');
        $action = trim((string) ($this->payload['action'] ?? ''));
        $orderId = $this->payload['order_id'] ?? null;
        $paymentId = $this->payload['payment_id'] ?? null;

        $mail = (new MailMessage)
            ->subject('Shopix - ' . $title)
            ->greeting('Hola ' . (($notifiable->name ?? '')))
            ->line($message);

        if (!empty($orderId)) {
            $mail->line('Pedido relacionado: #' . $orderId);
        }

        if (!empty($paymentId)) {
            $mail->line('Pago relacionado: #' . $paymentId);
        }

        if ($action !== '') {
            $mail->line('Acción: ' . $action);
        }

        return $mail->line('Gracias por usar Shopix.');
    }

    private function resolveTargetUrl(object $notifiable): ?string
    {
        $orderId = $this->payload['order_id'] ?? null;
        $paymentId = $this->payload['payment_id'] ?? null;

        if (!$orderId) {
            return null;
        }

        $order = SalesOrder::query()
            ->select('id', 'user_id', 'tenant_id')
            ->find($orderId);

        if (!$order) {
            return null;
        }

        if ((int) $order->user_id === (int) ($notifiable->id ?? 0)) {
            return url('/publicOrder/' . $order->id);
        }

        if (!empty($notifiable->tenant_id) && (int) $order->tenant_id === (int) $notifiable->tenant_id) {
            return url('/sales/' . $order->id) . ($paymentId ? '#payment-' . $paymentId : '');
        }

        return null;
    }
}
