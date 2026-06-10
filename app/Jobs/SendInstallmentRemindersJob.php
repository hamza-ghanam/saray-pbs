<?php

namespace App\Jobs;

use App\Mail\InstallmentReminderMail;
use App\Models\Installment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInstallmentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [60, 300];

    public function __construct(
        public readonly Installment $installment,
    ) {}

    public function handle(): void
    {
        $this->installment->load('booking.customerInfos');

        $customers = $this->installment->booking->customerInfos;

        if ($customers->isEmpty()) {
            Log::warning('InstallmentReminder: No customers found.', [
                'installment_id' => $this->installment->id,
                'booking_id'     => $this->installment->booking_id,
            ]);
            return;
        }

        foreach ($customers as $customer) {
            if (empty($customer->email)) {
                continue;
            }

            Mail::to($customer->email)
                ->send(new InstallmentReminderMail($this->installment, $customer));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('InstallmentReminder: Job failed.', [
            'installment_id' => $this->installment->id,
            'error'          => $e->getMessage(),
        ]);
    }
}
