<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{

    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function storeDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string',
        ]);

        $user = $request->user();

        // Update if the token already exists for this user, otherwise create new.
        $deviceToken = DeviceToken::updateOrCreate(
            ['user_id' => $user->id, 'token' => $request->input('token')],
            ['device_type' => $request->input('device_type')]
        );

        return response()->json([
            'message' => 'Device token stored successfully.',
            'data' => $deviceToken
        ]);
    }


    public function sendPushNotification(Request $request)
    {
        // Validate that a device token is provided.
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $deviceToken = $validated['token'];

        $title = "Notification Test";
        $body  = "This is a test notification.";
        $data  = [
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            // Send the notification. The FCMService method accepts a single token or an array.
            $sent = $this->fcmService->sendPushNotification($deviceToken, $title, $body, $data);

            if (!$sent) {
                return response()->json(['error' => 'Notification failed to send.'], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json(['message' => 'Notification sent successfully.'], \Illuminate\Http\Response::HTTP_OK);
        } catch (\Throwable $e) {
            \Log::error('Error sending push notification: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while sending the notification.'], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
