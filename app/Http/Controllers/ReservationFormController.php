<?php

namespace App\Http\Controllers;

use App\Mail\ReservationFormMail;
use App\Models\Approval;
use App\Models\Booking;
use App\Models\ReservationForm;
use App\Models\SigningLink;
use App\Models\Unit;
use App\Services\PaymentPlanService;
use App\Services\DocumentSignatureService;
use App\Enums\DocumentType;
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

        $companySignedAt = now()->copy();

        $existingRF = ReservationForm::where('booking_id', $booking->id)->first();

        if ($existingRF) {
            $hasSigningLinks = SigningLink::query()
                ->whereMorphedTo('documentable', $existingRF)
                ->where('document_type', DocumentType::RF->value)
                ->exists();

            if ($hasSigningLinks) {
                return response()->json([
                    'error' => 'Reservation Form cannot be regenerated because it has already been sent for signature.'
                ], Response::HTTP_CONFLICT);
            }
        }

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
                'companySignedAt' => $companySignedAt,
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

            if ($existingRF) {
                $existingRF->update([
                    'file_path' => $filePath,
                    'status' => 'Pending',
                    'company_signed_at' => $companySignedAt,
                ]);
            } else {
                // 7. Create a new ReservationForm record with status = "Pending"
                ReservationForm::create([
                    'booking_id' => $booking->id,
                    'file_path' => $filePath,
                    'status' => 'Pending',
                    'company_signed_at' => $companySignedAt,
                ]);
            }

            DB::commit();



            // Send it by email!
            /*
            foreach ($booking->customerInfos as $customer) {
                Mail::to($customer->email)->queue(new ReservationFormMail($booking, $fileName));
            }
            */


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
     * Send Reservation Form (RF) signing links to booking customers.
     *
     * - Requires authenticated user with "generate reservation form" permission.
     * - If user has role "Sales", they can only send for signature for bookings they created.
     * - Booking must be in status "RF Pending".
     * - ReservationForm must exist and must have a generated PDF (file_path).
     * - Sends one unique signing link per recipient (customerInfos where requires_signature=true).
     * - If an active pending link exists for the same recipient & document, it will be marked as expired and a new link is generated.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/rf/send-for-signature",
     *     summary="Send RF for signature (one link per signer)",
     *     tags={"Bookings/RF"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=137)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="RF signing links sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="RF signing link(s) sent successfully."),
     *             @OA\Property(property="sent", type="integer", example=2),
     *             @OA\Property(property="created", type="integer", example=2),
     *             @OA\Property(
     *                 property="recipients",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"email","url","signing_link_id"},
     *                     @OA\Property(property="email", type="string", format="email", example="customer1@example.com"),
     *                     @OA\Property(property="name", type="string", nullable=true, example="John Doe"),
     *                     @OA\Property(property="url", type="string", example="https://frontend.example.com/sign/PLAIN_TOKEN"),
     *                     @OA\Property(property="signing_link_id", type="integer", example=999)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks permission or Sales user trying to access another user's booking",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="Unauthorized")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Forbidden")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found or ReservationForm not found",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Booking not found")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Reservation Form not found for this booking. Generate RF first.")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable – booking not in RF Pending, missing PDF, or no signer emails",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Cannot send for signature unless booking status is 'RF Pending'.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Reservation Form PDF is missing. Generate RF again.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="No customer emails found for this booking.")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function sendForSignature(Request $request, int $bookingId)
    {
        $user = $request->user();

        if (!$user->can('generate reservation form')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // 1) Load booking + customerInfos
        $booking = Booking::with(['customerInfos'])->find($bookingId);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // Optional: Sales فقط على حجوزاته (بنفس منطقك السابق)
        if ($user->hasRole('Sales') && (int) $booking->created_by !== (int) $user->id) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        Log::info("User {$user->id} sent a Reservation Form for booking {$booking->id} for signature.");

        // 2) Must be RF Pending
        if ($booking->status !== Booking::STATUS_RF_PENDING) {
            return response()->json([
                'error' => 'Cannot send for signature unless booking status is "RF Pending".'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3) Ensure ReservationForm exists and has a generated PDF
        $rf = ReservationForm::where('booking_id', $booking->id)->first();
        if (!$rf) {
            return response()->json([
                'error' => 'Reservation Form not found for this booking. Generate RF first.'
            ], Response::HTTP_NOT_FOUND);
        }

        if (empty($rf->file_path)) {
            return response()->json([
                'error' => 'Reservation Form PDF is missing. Generate RF again.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 4) Recipients from customerInfos
        $recipients = $booking->customerInfos
            ->where('requires_signature', true)
            ->map(function ($c) {
                return [
                    'email' => $c->email,
                    'name'  => $c->name_en ?? null,
                ];
            })
            ->filter(fn ($r) => !empty($r['email']))
            ->unique('email')
            ->values()
            ->toArray();

        if (empty($recipients)) {
            return response()->json([
                'error' => 'No customer emails found for this booking.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 5) Call service (one token per recipient)
        /** @var DocumentSignatureService $signatureService */
        $signatureService = app(DocumentSignatureService::class);

        $result = $signatureService->send(
            signable: $booking,
            documentable: $rf,
            type: DocumentType::RF,
            recipients: $recipients,
        );

        return response()->json([
            'message' => 'RF signing link(s) sent successfully.',
            'sent'    => $result['sent'],
            'created' => $result['created'],
            'recipients' => $result['recipients'],
        ], Response::HTTP_OK);
    }

    /**
     * Submit a signature for a Reservation Form (RF) using a one-time token.
     *
     * - Public endpoint (no auth) protected by one-time token.
     * - Accepts a PNG signature as base64 (either raw base64 or data URL).
     * - Marks the signing link as used (status -> expired) and stores signature image path.
     * - If all required signers completed, the system finalises the RF (generates final signed PDF).
     *
     * @OA\Post(
     *     path="/sign/rf/{token}/submit",
     *     summary="Submit RF signature (base64 PNG)",
     *     tags={"Signing/RF"},
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="One-time signing token (plain token received by email)",
     *         required=true,
     *         @OA\Schema(type="string", example="9f3a2b7c8d...plain_token_here...")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"signature"},
     *             @OA\Property(
     *                 property="signature",
     *                 type="string",
     *                 description="Signature as PNG base64. Can be raw base64 or prefixed with 'data:image/png;base64,'.",
     *                 example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/6XfW8kAAAAASUVORK5CYII="
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Signature submitted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Signature submitted successfully."),
     *             @OA\Property(
     *                 property="finalized",
     *                 type="boolean",
     *                 description="True if this submission completed all required signatures and the RF was finalised.",
     *                 example=false
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token / link not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid link.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=410,
     *         description="Link expired or already used",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="This link is no longer valid.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error, invalid signature encoding, too large, or wrong document type",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="The signature field is required."),
     *                     @OA\Property(
     *                         property="errors",
     *                         type="object",
     *                         example={"signature":{"The signature field is required."}}
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Invalid signature encoding.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Signature image is too large.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Invalid document type for this endpoint.")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to submit signature.")
     *         )
     *     )
     * )
     */
    public function submitSignature(Request $request, string $token)
    {
        $request->validate([
            'signature' => ['required', 'string'], // data:image/png;base64,... OR raw base64
        ]);

        $tokenHash = hash('sha256', $token);

        DB::beginTransaction();
        try {
            $link = SigningLink::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (!$link) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid link.'], Response::HTTP_NOT_FOUND);
            }

            if ($link->status !== SigningLink::STATUS_PENDING || $link->signed_at !== null) {
                DB::rollBack();
                return response()->json(['error' => 'This link is no longer valid.'], 410);
            }

            if ($link->expires_at !== null && $link->expires_at->isPast()) {
                $link->forceFill(['status' => SigningLink::STATUS_EXPIRED])->save();
                DB::commit();
                return response()->json(['error' => 'This link has expired.'], 410);
            }

            $rf = $link->documentable;
            if (!$rf instanceof ReservationForm) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid document type for this endpoint.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Decode base64
            $sig = trim((string) $request->input('signature'));
            $sig = preg_replace('/^data:image\/png;base64,/', '', $sig);
            $sig = str_replace(' ', '+', $sig);

            $binary = base64_decode($sig, true);
            if ($binary === false) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid signature encoding.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (strlen($binary) > 1_500_000) {
                DB::rollBack();
                return response()->json(['error' => 'Signature image is too large.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Save signature image
            $signaturePath = 'signatures/rf/' . $link->id . '_' . now()->format('Ymd_His') . '.png';
            Storage::disk('local')->put($signaturePath, $binary);

            // Consume link (after submit => expired)
            $link->forceFill([
                'signature_image_path' => $signaturePath,
                'signed_at'            => now(),
                'status'               => SigningLink::STATUS_EXPIRED,
                'client_ip'            => $request->ip(),
                'user_agent'           => (string) $request->userAgent(),
            ])->save();

            // Finalise RF if complete
            $finalized = $this->finalizeRfIfComplete($rf);

            DB::commit();

            return response()->json([
                'message'   => 'Signature submitted successfully.',
                'finalized' => $finalized,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('RF submit error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit signature.'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * - Requires authenticated user with "approve reservation form" permission.
     * - Will NOT approve if required customer signatures are incomplete.
     * - Will NOT approve if signed PDF is missing.
     * - On success:
     *   - ReservationForm.status -> "Approved"
     *   - Booking.status -> "SPA Pending"
     *   - Creates an Approval record
     *   - Emails the final signed RF PDF to all booking customers that have an email.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/rf/approve",
     *     summary="Approve a signed Reservation Form",
     *     tags={"Bookings/RF"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="Booking ID whose Reservation Form will be approved",
     *         required=true,
     *         @OA\Schema(type="integer", example=137)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reservation Form approved successfully and emailed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Reservation form has been approved and sent to customer(s)."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks 'approve reservation form' permission",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Booking or Reservation Form not found for the given booking",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Booking] 137")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable – missing signatures, not signed status, or missing signed PDF",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Cannot approve Reservation Form: required signatures are incomplete.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Cannot approve a Reservation Form unless it is Signed.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Cannot approve: signed PDF is missing.")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function approve(Request $request, $bookingId)
    {
        $user = $request->user();

        if (!$user->can('approve reservation form')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $booking = Booking::with(['customerInfos', 'reservationForm'])->findOrFail($bookingId);
        $rf = $booking->reservationForm()->firstOrFail();

        // ✅ block if signatures incomplete OR not finalized
        if (!$this->finalizeRfIfComplete($rf)) {
            return response()->json([
                'error' => 'Cannot approve Reservation Form: required signatures are incomplete.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($rf->status !== 'Signed') {
            return response()->json([
                'error' => 'Cannot approve a Reservation Form unless it is Signed.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (empty($rf->signed_file_path) || !Storage::disk('local')->exists($rf->signed_file_path)) {
            return response()->json([
                'error' => 'Cannot approve: signed PDF is missing.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Approve
        $rf->status = 'Approved';
        $rf->save();

        // Booking -> Pending SPA
        $booking->status = Booking::STATUS_SPA_PENDING;
        $booking->save();

        Approval::create([
            'ref_id'        => $rf->id,
            'ref_type'      => ReservationForm::class,
            'approved_by'   => $user->id,
            'approval_type' => $user->getRoleNames()->first(),
            'status'        => 'Approved',
        ]);

        // Send final signed PDF as attachment (like before)
        $fileName = 'RF_' . $booking->id . '_' . $booking->unit->unit_no . '_SIGNED.pdf';

        foreach ($booking->customerInfos as $customer) {
            if (empty($customer->email)) continue;

            Mail::to($customer->email)->queue(
                new ReservationFormMail($booking, $customer, $fileName, $rf->signed_file_path)
            );
        }

        return response()->json(['message' => 'Reservation form has been approved and sent to customer(s).'], Response::HTTP_OK);
    }

    private function finalizeRfIfComplete(ReservationForm $rf): bool
    {
        // already finalized
        if (!empty($rf->signed_at) && !empty($rf->signed_file_path)) {
            return true;
        }

        $booking = Booking::with(['customerInfos', 'installments.paymentPlan', 'unit', 'paymentPlan'])
            ->find($rf->booking_id);

        if (!$booking) {
            return false;
        }

        $requiredEmails = $booking->customerInfos
            ->where('requires_signature', true)
            ->pluck('email')
            ->filter()
            ->map(fn ($e) => strtolower(trim($e)))
            ->unique()
            ->values();

        if ($requiredEmails->isEmpty()) {
            return false;
        }

        // ✅ strict: distinct emails signed
        $signedDistinct = SigningLink::query()
            ->whereMorphedTo('documentable', $rf)
            ->where('document_type', DocumentType::RF->value)
            ->whereIn('recipient_email', $requiredEmails->all())
            ->whereNotNull('signed_at')
            ->whereNotNull('signature_image_path')
            ->distinct('recipient_email')
            ->count('recipient_email');

        if ($signedDistinct !== $requiredEmails->count()) {
            return false;
        }

        // Map signatures by email -> absolute path
        $signaturesByEmail = SigningLink::query()
            ->whereMorphedTo('documentable', $rf)
            ->where('document_type', DocumentType::RF->value)
            ->whereIn('recipient_email', $requiredEmails->all())
            ->whereNotNull('signed_at')
            ->whereNotNull('signature_image_path')
            ->orderByDesc('signed_at')
            ->get()
            ->unique(fn ($l) => strtolower(trim($l->recipient_email))) // 🔥 آخر توقيع لكل إيميل
            ->mapWithKeys(function ($l) {
                return [
                    strtolower(trim($l->recipient_email)) => [
                        'path'      => Storage::disk('local')->path($l->signature_image_path),
                        'signed_at' => $l->signed_at,
                    ],
                ];
            })
            ->toArray();

        // your calc (if needed)
        if ($booking->paymentPlan) {
            $booking->paymentPlan->dld_fee = round($booking->price * ($booking->paymentPlan->dld_fee_percentage / 100), 2);
        }

        $data = [
            'booking'           => $booking,
            'customerInfos'     => $booking->customerInfos,
            'paymentPlan'       => $booking->paymentPlan,
            'installments'      => $booking->installments,
            'unit'              => $booking->unit,
            'signaturesByEmail' => $signaturesByEmail,
            'finalSignedAt'     => now(),
            'companySignedAt'   => $rf->company_signed_at,
        ];

        $pdf = MYPDF::loadView('pdf.reservation_form', $data, [], [
            'instanceConfigurator' => function ($mpdf) {
                $mpdf->autoScriptToLang = true;
                $mpdf->autoLangToFont = true;
                $mpdf->allow_charset_conversion = false;
            }
        ]);

        $content = $pdf->output();

        $fileName = 'RF_SIGNED_FINAL_' . $rf->booking_id . '_' . now()->format('Ymd_His') . '.pdf';
        $path = 'reservation_forms/signed/' . $fileName;

        Storage::disk('local')->put($path, $content);

        $rf->forceFill([
            'signed_at'        => now(),
            'signed_file_path' => $path,
            'status'           => 'Signed',
        ])->save();

        return true;
    }
}
