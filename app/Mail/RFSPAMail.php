<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\CustomerInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RFSPAMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;
    public $customer;
    public string $fileName;
    public string $signedFilePath;
    public string $docType;
    public string $subjectLine;
    public string $viewName;


    /** @var int Number of queue retries */
    public $tries = 3;

    /** @var array<int> Backoff seconds between retries */
    public $backoff = [30, 120];

    public function __construct($booking, $customer, $fileName, $signedFilePath, $docType, $subjectLine, $viewName)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->fileName = $fileName;
        $this->signedFilePath = $signedFilePath;
        $this->docType = $docType;
        $this->subjectLine = $subjectLine;
        $this->viewName = $viewName;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view($this->viewName)
            ->attach(storage_path("app/private/{$this->signedFilePath}"), [
                'as'   => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
