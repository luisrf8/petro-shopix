<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingDispatchGuidesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?Tenant $tenant,
        public array $report
    ) {}

    public function build()
    {
        $subject = 'Guias de despacho pendientes por facturar - ' . ucfirst((string) ($this->report['label'] ?? 'reporte'));

        return $this->subject($subject)
            ->view('emails.pending-dispatch-guides-report');
    }
}