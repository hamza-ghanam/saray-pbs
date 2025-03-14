<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ReservationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Symfony\Component\HttpFoundation\Response;

class ReservationFormController extends Controller
{
    /**
     * Generate or retrieve an existing Reservation Form for a booking.
     *
     * If a Reservation Form already exists, the existing record and its PDF URL are returned (HTTP 200).
     * Otherwise, a new PDF is generated, stored, and a new ReservationForm record is created (HTTP 201).
     *
     * @OA\Get(
     *     path="/bookings/{bookingId}/reservation-form",
     *     summary="Generate or retrieve a Reservation Form PDF for a booking",
     *     tags={"ReservationForm"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking for which the Reservation Form is generated",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation Form already exists, returning existing PDF URL",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="reservation_form",
     *                 type="object",
     *                 description="Existing ReservationForm record",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="file_path", type="string", example="reservation_forms/RF_42.pdf"),
     *                 @OA\Property(property="status", type="string", example="Pending")
     *             ),
     *             @OA\Property(
     *                 property="rf_url",
     *                 type="string",
     *                 format="uri",
     *                 example="http://your-domain.test/storage/reservation_forms/RF_42.pdf"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="New Reservation Form created and returning new PDF URL",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="reservation_form",
     *                 type="object",
     *                 description="Newly created ReservationForm record",
     *                 @OA\Property(property="id", type="integer", example=11),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="file_path", type="string", example="reservation_forms/RF_42.pdf"),
     *                 @OA\Property(property="status", type="string", example="Pending")
     *             ),
     *             @OA\Property(
     *                 property="rf_url",
     *                 type="string",
     *                 format="uri",
     *                 example="http://your-domain.test/storage/reservation_forms/RF_42.pdf"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (no permission to generate sales offer)"),
     *     @OA\Response(response=404, description="Booking not found or existing PDF file missing"),
     *     @OA\Response(response=422, description="Invalid booking/unit status or other validation error")
     * )
     */
    public function generate(Request $request, $bookingId)
    {
        $user = $request->user();

        // Check user permissions (Sales or Broker can generate a sales offer)
        if (!$user->can('generate reservation form')) {
            abort(403, 'Unauthorized');
        }

        // 1. Retrieve the booking
        $booking = Booking::with('unit')->find($bookingId);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        Log::info("User {$user->id} generated a Reservation Form for booking {$booking->id}");

        // 2. Check the booking and unit status logic
        //    e.g., only generate RF if:
        //        - the unit has status "Booked"
        //        - the booking status is "RF Pending", "SPA Pending", or "Booked"
        $validBookingStatuses = ['RF Pending', 'SPA Pending', 'Booked'];
        if ($booking->unit->status !== 'Booked' ||
            !in_array($booking->status, $validBookingStatuses)) {
            return response()->json([
                'error' => 'Cannot generate RF unless unit is "Booked" and booking is in "RF Pending", "SPA Pending", or "Booked" status.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3. Ensure only one Reservation Form per booking (or per unit)
        //    If you want exactly one RF per booking:
        $existingRF = ReservationForm::where('booking_id', $booking->id)->first();
        if ($existingRF) {
            if (Storage::disk('public')->exists($existingRF->file_path)) {
                // Build a publicly accessible URL if you ran `php artisan storage:link`
                // e.g., /storage/reservation_forms/RF_123.pdf
                $pdfUrl = asset('storage/' . $existingRF->file_path);

                return response()->json([
                    'reservation_form' => $existingRF,
                    'rf_url' => $pdfUrl
                ], 200);
            } else {
                // If the file is missing, you could re-generate or return an error
                return response()->json([
                    'error' => 'Existing Reservation Form file not found on disk.'
                ], 404);
            }
        }

        // 4. Generate the PDF (using your Blade view)
        $pdf = PDF::loadView('pdf.reservation_form', [
            'booking' => $booking,
            'customerInfo' => $booking->customerInfo,
            'paymentPlan' => $booking->paymentPlan,
            'unit' => $booking->unit,
        ]);

        // Get the raw PDF content
        $pdfContent = $pdf->output();

        // 5. Store the PDF file on disk
        $fileName = 'RF_' . $booking->id . '.pdf';
        $filePath = 'reservation_forms/' . $fileName; // relative to "public" disk
        Storage::disk('public')->put($filePath, $pdfContent);

        // 6. Create a new ReservationForm record with status = "Pending"
        $rf = ReservationForm::create([
            'booking_id' => $booking->id,
            'file_path' => $filePath,
            'status' => 'Pending',
        ]);

        $pdfUrl = asset('storage/' . $filePath);

        return response()->json([
            'reservation_form' => $rf,
            'rf_url' => $pdfUrl,
        ], 201);
    }

    /**
     * Upload a signed Reservation Form (RF) file.
     *
     * This endpoint accepts a PDF file for a specific ReservationForm record.
     * If the RF is no longer "Pending", only users with "CEO" or "System Maintenance" roles
     * can overwrite it. Otherwise, a 409 (Conflict) error is returned.
     *
     * @OA\Post(
     *     path="/reservation-forms/{id}/upload-signed",
     *     summary="Upload a signed Reservation Form file",
     *     tags={"ReservationForm"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the ReservationForm to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"signed_rf"},
     *                 @OA\Property(
     *                     property="signed_rf",
     *                     type="string",
     *                     format="binary",
     *                     description="The signed Reservation Form file (PDF)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signed RF uploaded and record updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="reservation_form",
     *                 type="object",
     *                 description="Updated ReservationForm record",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="file_path", type="string", example="reservation_forms/signed/RF_42_signed.pdf"),
     *                 @OA\Property(property="status", type="string", example="Signed")
     *             ),
     *             @OA\Property(
     *                 property="rf_url",
     *                 type="string",
     *                 format="uri",
     *                 example="http://your-domain.test/storage/reservation_forms/signed/RF_42_signed.pdf"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Reservation Form not found"),
     *     @OA\Response(response=409, description="Conflict if the RF is already uploaded and user isn't CEO/System Maintenance"),
     *     @OA\Response(response=422, description="Validation error (e.g., file not a PDF)")
     * )
     */
    public function uploadSigned(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is uploading a signed RF for ReservationForm ID: {$id}");

        if (!$user->can('upload signed reservation form')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // 2. Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'signed_rf' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 3. Retrieve the ReservationForm record
        $reservationForm = ReservationForm::find($id);
        if (!$reservationForm) {
            return response()->json(['error' => 'Reservation Form not found'], Response::HTTP_NOT_FOUND);
        }

        $role = $user->getRoleNames()->first();

        if ($reservationForm->status !== 'Pending' && !in_array($role, ['CEO', 'System Maintenance'])) {
            return response()->json(
                ['error' => 'The signed Reservation Form was already uploaded'],
                Response::HTTP_CONFLICT);
        }

        // 4. Store the signed RF file
        $file = $request->file('signed_rf');
        $originalName = $file->getClientOriginalName();
        $fileName = 'RF_' . $reservationForm->booking_id . '_signed.' . $file->getClientOriginalExtension();
        $filePath = 'reservation_forms/signed/' . $fileName;

        Storage::disk('public')->putFileAs('reservation_forms/signed', $file, $fileName);

        // 5. Update the ReservationForm record
        $reservationForm->file_path = $filePath;
        $reservationForm->status = 'Signed';
        $reservationForm->save();
        $pdfUrl = asset('storage/' . $filePath);

        return response()->json([
            'reservation_form' => $reservationForm,
            'rf_url' => $pdfUrl,
        ], Response::HTTP_OK);
    }

    /**
     * Approve a Reservation Form (RF).
     *
     * This endpoint sets the Reservation Form status to "Approved" if it is currently "Signed".
     * It also updates the associated booking's status to "SPA Pending".
     *
     * @OA\Post(
     *     path="/reservation-forms/{id}/approve",
     *     summary="Approve a signed Reservation Form",
     *     tags={"ReservationForm"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the ReservationForm to approve",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation Form approved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="reservation_form",
     *                 type="object",
     *                 description="Updated ReservationForm record",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="file_path", type="string", example="reservation_forms/signed/RF_42_signed.pdf"),
     *                 @OA\Property(property="status", type="string", example="Approved")
     *             ),
     *             @OA\Property(
     *                 property="rf_url",
     *                 type="string",
     *                 example="http://your-domain.test/storage/reservation_forms/signed/RF_42_signed.pdf",
     *                 description="Path or URL to the approved Reservation Form file"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks 'approve reservation form' permission)"),
     *     @OA\Response(response=404, description="Reservation Form not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot approve unless the form is Signed or other validation error"
     *     )
     * )
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to approve ReservationForm ID: {$id}");

        // 1. Check user permission (adjust the ability name as needed)
        if (!$user->can('approve reservation form')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 2. Retrieve the ReservationForm record
        $reservationForm = ReservationForm::find($id);
        if (!$reservationForm) {
            return response()->json(['error' => 'Reservation Form not found'], Response::HTTP_NOT_FOUND);
        }

        // 3. Check if it's in a state that can be approved
        //    e.g., only "Signed" forms can be approved
        if ($reservationForm->status !== 'Signed') {
            return response()->json([
                'error' => 'Cannot approve a Reservation Form unless it is Signed.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 4. Approve the RF (set status to "Approved")
        $reservationForm->status = 'Approved';
        $reservationForm->save();

        // 5. Change the status ob the Booking to be "Pending SPA"
        if ($reservationForm->booking) {
            $reservationForm->booking->status = 'SPA Pending';
            $reservationForm->booking->save();
        }

        // 6. Return the updated record
        return response()->json([
            'reservation_form' => $reservationForm,
            'rf_url' => $reservationForm->file_path,
        ], Response::HTTP_OK);
    }
}
