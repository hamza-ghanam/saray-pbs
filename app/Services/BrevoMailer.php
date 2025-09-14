<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;

class BrevoMailer
{
    protected TransactionalEmailsApi $api;
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', config('services.brevo.key'));

        $this->api = new TransactionalEmailsApi(null, $config);
        $this->fromEmail = config('services.brevo.sender_email');
        $this->fromName = config('services.brevo.sender_name', 'App');
    }

    /**
     * $to = [['email'=>'user@example.com','name'=>'User']]
     */
    public function sendHtml(array $to, string $subject, string $html, array $attachments = []): string
    {
        $payload = [
            'sender'      => ['email' => $this->fromEmail, 'name' => $this->fromName],
            'to'          => $to,
            'subject'     => $subject,
            'htmlContent' => $html,
            'trackClicks' => false,     // ✅ Most Important
            'trackOpens'  => false,
        ];

        if (!empty($attachments)) {
            // $attachments = [['name' => 'file.pdf', 'content' => base64_encode(file_get_contents(...))], ...]
            $payload['attachment'] = $attachments;
        }

        $resp = $this->api->sendTransacEmail(new SendSmtpEmail($payload));
        return $resp->getMessageId();
    }

    public function sendView(array $to, string $subject, string $view, array $data = [], array $attachments = []): string
    {
        $html = view($view, $data)->render();

        return $this->sendHtml($to, $subject, $html, $attachments);
    }
}
