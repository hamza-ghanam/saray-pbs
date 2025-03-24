<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FCMService
{
    protected Messaging $messaging;

    /**
     * Inject the Firebase Messaging instance.
     */
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send a push notification using Firebase Cloud Messaging.
     *
     * @param string $deviceToken The FCM token for the target device.
     * @param string $title       The title of the notification.
     * @param string $body        The body text of the notification.
     * @param array  $data        Additional custom data to send with the notification.
     *
     * @return bool True if sent successfully; false otherwise.
     */
    public function sendPushNotification($deviceTokens, string $title, string $body, array $data = []): bool
    {
        // If a single token is provided, wrap it in an array
        if (!is_array($deviceTokens)) {
            $deviceTokens = [$deviceTokens];
        }

        // Create the notification payload.
        $notification = Notification::create($title, $body);

        // Create the cloud message without a predefined target
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withAndroidConfig([
                'ttl' => '3600s',
                'priority' => 'high',
            ])
            ->withApnsConfig([
                'headers' => [
                    'apns-priority' => '10',  // For iOS devices
                ],
            ]);

        try {
            // Send the message to all device tokens (even if it's only one)
            $this->messaging->sendMulticast($message, $deviceTokens);
            return true;
        } catch (\Throwable $e) {
            Log::error('Error sending FCM push notification: ' . $e->getMessage());
            return false;
        }
    }
}
