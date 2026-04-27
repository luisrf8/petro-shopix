<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Support\WorkflowNotifier;
use Illuminate\Console\Command;

class SendAppointmentConfirmationReminderCommand extends Command
{
    protected $signature = 'appointments:send-confirmation-reminders';

    protected $description = 'Envía recordatorios una hora antes de la cita para confirmar asistencia.';

    public function handle(): int
    {
        $windowStart = now()->addHour()->subMinutes(2);
        $windowEnd = now()->addHour()->addMinutes(2);

        $appointments = Appointment::query()
            ->with(['service', 'customer'])
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNotNull('customer_id')
            ->whereNull('confirmation_reminder_sent_at')
            ->get();

        $sentCount = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment->customer) {
                continue;
            }

            $serviceLabel = (string) ($appointment->service->display_name ?? $appointment->service->name ?? 'Servicio');

            WorkflowNotifier::notifyUser($appointment->customer, [
                'title' => 'Confirma tu asistencia',
                'message' => $serviceLabel . ' · Tu cita inicia en 1 hora. Confirma si asistirás desde "Mis citas".',
                'type' => 'info',
                'tenant_id' => (int) $appointment->tenant_id,
                'order_id' => null,
                'action' => 'appointment_confirmation_request',
                'meta' => [
                    'appointment_id' => (int) $appointment->id,
                    'status' => (string) $appointment->status,
                    'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                    'actor' => 'Sistema',
                    'note' => 'Recordatorio automático 1 hora antes de la cita.',
                ],
            ]);

            $appointment->confirmation_reminder_sent_at = now();
            $appointment->save();
            $sentCount += 1;
        }

        $this->info('Recordatorios enviados: ' . $sentCount);

        return self::SUCCESS;
    }
}
