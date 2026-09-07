<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleMonitoringChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $vehicleName,
        public readonly string $plate,
        public readonly string $vehicleType,
        public readonly ?int $previousViolationCount,
        public readonly int $currentViolationCount,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Biển số {$this->plate} thay đổi số lỗi phạt nguội",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.traffic-fines.vehicle-monitoring-changed',
            with: [
                'lookupUrl' => route('traffic-fines.result', [
                    'plate' => $this->plate,
                    'vehicle_type' => $this->vehicleType,
                    'api_version' => 'v2',
                ]),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
