<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
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
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
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
}
