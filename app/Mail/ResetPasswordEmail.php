<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class ResetPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public string $resetUrl;

    public function __construct($user)
    {
        $this->user = $user;

        $token = Password::createToken($user);

        $frontend = config('services.frontend_url', config('app.url'));

        $this->resetUrl = rtrim($frontend, '/') . '/reset-password?token='
            . urlencode($token) . '&email=' . urlencode($user->email);
    }

    public function build()
    {
        return $this->subject('Reset Your Password')
            ->view('emails.reset_password')
            ->with([
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
