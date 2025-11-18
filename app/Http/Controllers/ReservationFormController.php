<?php

namespace App\Http\Controllers;

use App\Mail\ReservationFormMail;
use App\Models\Approval;
use App\Models\Booking;
use App\Models\ReservationForm;
use App\Models\Unit;
use App\Services\PaymentPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as MYPDF;
use Symfony\Component\HttpFoundation\Response;

class ReservationFormController extends Controller
{
    protected PaymentPlanService $paymentPlanService;

    public function __construct(PaymentPlanService $paymentPlanService)
    {
        $this->paymentPlanService = $paymentPlanService;
    }

    /**
     * Generate or retrieve a Reservation Form for a booking.
     *
     * If a Reservation Form already exists (and its PDF file is present on disk), the existing PDF is emailed to the customer(s) (HTTP 200).
     * Otherwise, a new PDF is generated, stored, and emailed (HTTP 201).
     *
     * @OA\Get(
     *     path="/bookings/{bookingId}/rf",
     *     summary="Generate or download a Reservation Form PDF for a booking",
     *     tags={"Bookings/RF"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking for which to generate or retrieve the RF",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="New Reservation Form PDF generated and streamed successfully",
     *         @OA\MediaType(mediaType="application/pdf")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden (no permission to generate reservation form)"),
     *     @OA\Response(response=404, description="Booking not found or existing PDF file missing on disk"),
     *     @OA\Response(response=422, description="Validation or business-rule error (e.g. unit/booking status invalid)")
     * )
     */
    public function generate(Request $request, $bookingId)
    {
        $user = $request->user();

        // Check user permissions (Sales or Broker can generate a sales offer)
        if (!$user->can('generate reservation form')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // 1. Retrieve the booking
        $booking = Booking::with('unit')->find($bookingId);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // 2. Sales users may only act on bookings they created
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        Log::info("User {$user->id} generated a Reservation Form for booking {$booking->id}");

        // 3. Check the booking and unit status logic
        //    e.g., only generate RF if:
        //        - the unit has status "Booked"
        //        - the booking status is "RF Pending", "SPA Pending", or "Booked"
        $validBookingStatuses = [Booking::STATUS_RF_PENDING, Booking::STATUS_SPA_PENDING, Booking::STATUS_BOOKED];
        if ($booking->unit->status !== Unit::STATUS_BOOKED ||
            !in_array($booking->status, $validBookingStatuses)) {
            return response()->json([
                'error' => 'Cannot generate RF unless unit is "Booked" and booking is in "RF Pending", "SPA Pending", or "Booked" status.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $fileName = 'RF_' . $booking->id . '.pdf';

            // 4. Ensure only one Reservation Form per booking (or per unit)
            //    If you want exactly one RF per booking:
            /*
            $existingRF = ReservationForm::where('booking_id', $booking->id)->first();
            if ($existingRF) {
                if (Storage::disk('local')->exists($existingRF->file_path)) {
                    foreach ($booking->customerInfos as $customer) {
                        Mail::to($customer->email)->queue(new ReservationFormMail($booking, $fileName));
                    }

                    return response()->json(['message' => 'Reservation form emailed to customer(s) successfully.'], Response::HTTP_OK);
                } else {
                    // If the file is missing, you could re-generate or return an error
                    return response()->json([
                        'error' => 'Existing Reservation Form file not found on disk.'
                    ], Response::HTTP_NOT_FOUND);
                }
            }
            */

            $booking->paymentPlan->dld_fee = round($booking->price * ($booking->paymentPlan->dld_fee_percentage / 100), 2);

            $booking->load([
                'installments.paymentPlan',     // for grouping and headings
                'unit',
                'customerInfos'
            ]);

            $reservationData = [
                'booking' => $booking,
                'customerInfos' => $booking->customerInfos,
                'paymentPlan' => $booking->paymentPlan,
                'installments' => $booking->installments,
                'unit' => $booking->unit,
            ];

            // 5. Generate the PDF (using your Blade view)
            /*
             * // DomPDF - 28/03/2025
            $pdf = PDF::loadView('pdf.reservation_form', $reservationData);
            */

            // mPDF - 12/7/2025
            $pdf = MYPDF::loadView('pdf.reservation_form', $reservationData, [], [
                'instanceConfigurator' => function ($mpdf) {
                    $mpdf->showImageErrors = true; // Show errors related to images
                    $mpdf->debug = true; // Enable general debugging
                    $mpdf->autoScriptToLang = true;
                    $mpdf->autoLangToFont = true;
                    $mpdf->allow_charset_conversion = false; // This is often crucial for Arabic/RTL
                }
            ]);

            // Get the raw PDF content
            $pdfContent = $pdf->output();

            // 6. Store the PDF file on disk
            $filePath = 'reservation_forms/' . $fileName; // relative to "public" disk
            Storage::disk('local')->put($filePath, $pdfContent);

            $existingRF = ReservationForm::where('booking_id', $booking->id)->first();
            if ($existingRF) {
                $existingRF->update([
                    'file_path' => $filePath,
                    'status' => 'Pending',
                ]);
            } else {
                // 7. Create a new ReservationForm record with status = "Pending"
                ReservationForm::create([
                    'booking_id' => $booking->id,
                    'file_path' => $filePath,
                    'status' => 'Pending',
                ]);
            }

            DB::commit();

            // Send it by email!
            foreach ($booking->customerInfos as $customer) {
                Mail::to($customer->email)->queue(new ReservationFormMail($booking, $fileName));
            }

            // 8. Stream the newly created PDF
            return response($pdfContent, Response::HTTP_CREATED, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        } catch (\Exception $ex) {
            DB::rollback();
            Log::error("RF Booking ID: {$booking->id} Generation Error: " . $ex->getMessage());
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload a signed Reservation Form (RF) file for a booking.
     *
     * This endpoint accepts a PDF file for the ReservationForm associated with the given booking.
     * If the RF is no longer "Pending", only users with the "CEO" or "System Maintenance" roles
     * may overwrite it; otherwise a 409 Conflict is returned.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/rf/upload-signed",
     *     summary="Upload a signed Reservation Form for a booking",
     *     tags={"Bookings/RF"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking whose ReservationForm will be updated",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
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
     *                     description="The signed Reservation Form file (PDF, max 2 MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signed RF successfully uploaded",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Signed RF successfully uploaded"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – missing permission to upload signed RF"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reservation Form not found for the given booking"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict – RF already signed and user lacks override role"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (e.g., no file or wrong MIME type)"
     *     )
     * )
     */
    public function uploadSigned(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is uploading a signed RF for ReservationForm of booking ID: {$id}");

        if (!$user->can('upload signed reservation form')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 1. Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'signed_rf' => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Retrieve the Booking and reservationForm records
        $booking = Booking::findOrFail($id);
        $reservationForm = $booking
            ->reservationForm()   // relation query
            ->firstOrFail();      // throws ModelNotFound → JSON 404

        $role = $user->getRoleNames()->first();

        if ($role === 'Sales' && $booking->created_by !== $user->id) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($reservationForm->status !== 'Pending' && !in_array($role, ['CEO', 'System Maintenance'])) {
            return response()->json(
                ['error' => 'The signed Reservation Form was already uploaded'],
                Response::HTTP_CONFLICT);
        }

        // 3. Store the signed RF file
        $file = $request->file('signed_rf');
        $fileName = 'RF_' . $reservationForm->booking_id . '_signed.' . $file->getClientOriginalExtension();
        $filePath = 'reservation_forms/signed/' . $fileName;
        Storage::disk('local')->putFileAs('reservation_forms/signed', $file, $fileName);

        // 4. Update the ReservationForm record
        $reservationForm->update([
            'status' => 'Signed',
            'signed_at' => now(),
            'signed_file_path' => $filePath,
        ]);


        return response()->json(['message' => 'Signed reservation form has been successfully uploaded'], Response::HTTP_OK);
    }

    /**
     * Approve a signed Reservation Form.
     *
     * Sets the Reservation Form status to "Approved", updates the related Booking status to "SPA Pending",
     * and records an Approval entry.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/rf/approve",
     *     summary="Approve a signed Reservation Form",
     *     tags={"Bookings/RF"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking whose ReservationForm will be approved",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation Form approved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Reservation form has been approved"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks 'approve reservation form' permission"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reservation Form not found for the given booking"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot approve unless the form is Signed or other validation error"
     *     )
     * )
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to approve ReservationForm of booking ID: {$id}");

        // 1. Check user permission (adjust the ability name as needed)
        if (!$user->can('approve reservation form')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 2. Retrieve the ReservationForm record
        $reservationForm = Booking::findOrFail($id)
            ->reservationForm()
            ->firstOrFail();

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
            $reservationForm->booking->status = Booking::STATUS_SPA_PENDING;
            $reservationForm->booking->save();
        }

        // 6. Make approval
        Approval::create([
            'ref_id' => $reservationForm->id,
            'ref_type' => 'App\Models\ReservationForm',
            'approved_by' => $user->id,
            'approval_type' => $user->getRoleNames()->first(),
            'status' => 'Approved',
        ]);

        // 7. Return the updated record
        return response()->json(['message' => 'Reservation form has been approved'], Response::HTTP_OK);
    }
}
