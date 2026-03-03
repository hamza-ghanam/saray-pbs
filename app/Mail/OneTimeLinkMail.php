<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OneTimeLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30,120];

    public function __construct(
        public ?User $user = null,
        public ?string $otlUrl = null,
        public ?string $documentPath = null,
    )
    {}

    public function build()
    {
        if ($this->otlUrl) {
            return $this->subject(config('app.name') . ' | Registration Link')
                ->view('emails.otl');
        }

        $mail = $this->subject(config('app.name') . ' | Account Registration Approved')
            ->view('emails.user_approved');

        // If a document path is provided, attach it
        if (!empty($this->documentPath)) {
            $mail->attach(storage_path("app/private/{$this->documentPath}"), [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
