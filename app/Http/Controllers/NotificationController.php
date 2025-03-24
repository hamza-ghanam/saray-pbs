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

    /**
     * @OA\Post(
     *     path="/device-token",
     *     summary="Store or update the device token",
     *     description="Stores the device token for the authenticated user or updates an existing record.",
     *     operationId="storeDeviceToken",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Device token payload",
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="YOUR_DEVICE_TOKEN"),
     *             @OA\Property(property="device_type", type="string", example="android")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device token stored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Device token stored successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The given data was invalid.")
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Post(
     *     path="/notify",
     *     summary="Send a test push notification",
     *     description="Sends a test push notification to a device using its FCM token.",
     *     operationId="sendPushNotification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Device token payload",
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="YOUR_DEVICE_TOKEN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification sent successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Device token is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while sending the notification.")
     *         )
     *     )
     * )
     */
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
