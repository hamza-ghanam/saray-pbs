<?php

namespace App\Mail;

use App\Models\Installment;
use App\Models\CustomerInfo;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InstallmentReminderMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Installment   $installment,
        public readonly CustomerInfo  $customer,
    ) {}

    public function build(): self
    {
        return $this->subject('Payment Reminder — Installment Due in 7 Days')
            ->view('emails.installment_reminder');
    }
}
