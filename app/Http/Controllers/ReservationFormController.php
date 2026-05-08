<?php

namespace App\Http\Controllers;

use App\Actions\Signature\CustomerSignatureUploadConfig;
use App\Actions\Signature\FinalizeSignedDocumentConfig;
use App\Actions\Signature\FinalizeSignedDocumentService;
use App\Actions\Signature\SendForSignatureAction;
use App\Actions\Signature\SignatureSendConfig;
use App\Actions\Signature\SignatureSubmitConfig;
use App\Actions\Signature\SubmitSignatureAction;
use App\Actions\Signature\UploadCustomerSignatureAction;
use App\Enums\DocumentType;
use App\Mail\RFSPAMail;
use App\Models\Approval;
use App\Models\Booking;
use App\Models\ReservationForm;
use App\Models\GeneralSetting;
use App\Models\SigningLink;
use App\Models\Unit;
use App\Services\PaymentPlanService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
    public function generate(Request $request, $bookingId, PdfService $pdfService)
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

            $booking->paymentPlan->dld_fee = $booking->paymentPlan?->calculateDldFee($booking->price);

            $booking->load([
                'installments.paymentPlan',     // for grouping and headings
                'unit',
                'customerInfos'
            ]);

            $company = GeneralSetting::getGroup('company');
            $bank    = GeneralSetting::getGroup('bank');
            $escrow  = GeneralSetting::getGroup('escrow');

            $reservationData = [
                'booking'        => $booking,
                'customerInfos'  => $booking->customerInfos,
                'paymentPlan'    => $booking->paymentPlan,
                'installments'   => $booking->installments,
                'unit'           => $booking->unit,
                'companySignedAt'=> $companySignedAt,
                'company'        => $company,
                'bank'           => $bank,
                'escrow'         => $escrow,
            ];

            // 5. Generate the PDF (using your Blade view)
            /*
             * // DomPDF - 28/03/2025
            $pdf = PDF::loadView('pdf.reservation_form', $reservationData);
            */

            // mPDF - 12/7/2025
            // 6. Store the PDF file on disk
            $filePath = 'reservation_forms/' . $fileName; // relative to "public" disk
            $pdfContent = $pdfService->store('pdf.reservation_form', $reservationData, $filePath);

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
     * - Sends signing link(s) to recipients (customerInfos where requires_signature=true).
     * - If a recipient already signed the document, no new link is sent for that recipient.
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
     *         description="RF signing links processed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="RF signing link(s) processed successfully."),
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
    public function sendForSignature(Request $request, int $bookingId, SendForSignatureAction $action)
    {
        return $action->handle($request, $bookingId, new SignatureSendConfig(
            permission: 'generate reservation form',
            requiredBookingStatus: Booking::STATUS_RF_PENDING,
            documentModelClass: ReservationForm::class,
            documentTypeValue: DocumentType::RF->value,
            missingDocMessage: 'Reservation Form not found for this booking. Generate RF first.',
            missingPdfMessage: 'Reservation Form PDF is missing. Generate RF again.',
            successMessage: 'RF signing link(s) sent successfully.',
            finalizedStatuses: ['Signed', 'Approved'],
            alreadyFinalizedMessage: 'Document already finalized.',
        ));
    }

    /**
     * Submit a signature for a Reservation Form (RF) using a one-time token.
     *
     * - Public endpoint (no auth) protected by one-time token.
     * - Accepts a PNG signature as base64 (either raw base64 or data URL).
     * - Marks the signing link as used (status -> expired) and stores signature image path.
     * - Stores signature_source = "customer_self" (self-service signing by the customer).
     * - If all required signers completed, the system finalises the RF (generates final signed PDF).
     *
     * @OA\Post(
     *     path="/bookings/rf/{token}/sign",
     *     summary="Submit RF signature (base64 PNG)",
     *     tags={"Bookings/RF"},
     *     description="Public self-service signing flow. The customer submits their signature via a one-time token. The system stores signature_source as customer_self to distinguish it from staff-assisted uploads, which use staff_uploaded.",
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
     *             type="object",
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
     *         description="Signature submitted successfully. signature_source is customer_self.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Signature submitted successfully."),
     *             @OA\Property(
     *                 property="finalized",
     *                 type="boolean",
     *                 description="True if this submission completed all required signatures and the RF was finalised.",
     *                 example=false
     *             ),
     *             @OA\Property(property="signature_source", type="string", example="customer_self", description="Indicates that the signature was submitted directly by the customer through the self-service flow.")
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
    public function submitSignature(Request $request, string $token, SubmitSignatureAction $action, FinalizeSignedDocumentService $finalizer)
    {
        $cfg = new SignatureSubmitConfig(
            type: DocumentType::RF,
            expectedDocumentClass: ReservationForm::class,
            signatureDir: 'signatures/rf',
            invalidDocMessage: 'Invalid document type for this endpoint.',
        );

        $config = new FinalizeSignedDocumentConfig(
            type: DocumentType::RF,
            view: 'pdf.reservation_form',
            signedDir: 'reservation_forms/signed',
            filePrefix: 'RF_SIGNED_FINAL_',
        );

        // Customer self-service digital signature flow (signature_source should be customer_self inside the submit action/service).
        return $action->handle(
            $request,
            $token,
            $cfg,
            finalize: function ($rf) use ($finalizer, $config) {
                $signedPath = $finalizer->finalizeBookingIfComplete($rf, $rf->booking_id, $config);
                return !empty($signedPath);
            }
        );
    }

    /**
     * Upload customer signature (base64) for Reservation Form (RF) by staff.
     *
     * This endpoint allows staff to upload a customer's signature image instead of using
     * the email-based signing flow. It simulates the same behaviour as submitSignature:
     *
     * - No email is sent.
     * - Reuses/refreshes the signer record used for the signing flow without sending email.
     * - Stores signature image and marks link as signed.
     * - Sets signature_source = "staff_uploaded".
     * - Mirrors the regular digital signing flow, where self-service submitSignature stores signature_source = "customer_self".
     * - Triggers finalisation immediately if this upload completes all required signatures.
     * - customer_id must belong to this booking and must require signature.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/rf/upload-signature",
     *     summary="Upload customer signature for RF (staff-assisted)",
     *     tags={"Bookings/RF"},
     *     security={{"sanctum":{}}},
     *
     *     description="This endpoint allows staff to upload a customer's signature image instead of using the email-based signing flow. It simulates the same behaviour as submitSignature, but without sending email. The signing record is marked with signature_source as staff_uploaded, while the normal self-service submitSignature flow uses customer_self. If this upload completes all required signatures, the Reservation Form is finalized immediately.",
     *
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=137)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"customer_id","signature"},
     *
     *             @OA\Property(
     *                 property="customer_id",
     *                 type="integer",
     *                 example=55,
     *                 description="CustomerInfo ID of the signer (must belong to this booking and require signature)"
     *             ),
     *
     *             @OA\Property(
     *                 property="signature",
     *                 type="string",
     *                 description="Signature as PNG base64 (raw or data URL)",
     *                 example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA..."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer signature uploaded successfully. signature_source is staff_uploaded. The document may also be finalized immediately if this was the last required signature.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Customer signature uploaded successfully."),
     *             @OA\Property(property="finalized", type="boolean", example=false, description="True if this upload completed all required signatures and the RF was finalized immediately."),
     *             @OA\Property(property="signing_link_id", type="integer", example=1234),
     *             @OA\Property(property="signature_source", type="string", example="staff_uploaded", description="Indicates the signature was uploaded by staff on behalf of the customer instead of being submitted directly by the customer.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Booking or RF not found",
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
     *         response=409,
     *         description="Conflict (this signer already signed)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="This signer has already submitted a signature.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation or business rule error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Customer signature upload is only allowed when RF status is Pending.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="No signer with this customer_id was found for the booking.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Invalid signature encoding.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Signature image is too large.")
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
     *             @OA\Property(property="error", type="string", example="Failed to upload customer signature.")
     *         )
     *     )
     * )
     */
    public function uploadCustomerSignature(
        Request $request,
        int $bookingId,
        UploadCustomerSignatureAction $action,
        FinalizeSignedDocumentService $finalizer
    ) {
        $cfg = new CustomerSignatureUploadConfig(
            permission: 'upload signed reservation form',
            bookingRelation: 'reservationForm',
            expectedDocumentClass: ReservationForm::class,
            type: DocumentType::RF,
            signatureDir: 'signatures/rf',
            filePrefix: 'rf_signature',
            documentNotFoundMessage: 'Reservation Form not found for this booking. Generate RF first.',
            invalidStatusMessage: 'Customer signature upload is only allowed when RF status is Pending.',
        );

        $finalizeCfg = new FinalizeSignedDocumentConfig(
            type: DocumentType::RF,
            view: 'pdf.reservation_form',
            signedDir: 'reservation_forms/signed',
            filePrefix: 'RF_SIGNED_FINAL_',
        );

        return $action->handle(
            request: $request,
            bookingId: $bookingId,
            cfg: $cfg,
            finalize: fn ($rf) => !empty(
            $finalizer->finalizeBookingIfComplete(
                documentable: $rf,
                bookingId: $rf->booking_id,
                cfg: $finalizeCfg
            )
            )
        );
    }

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
    public function approve(Request $request, $bookingId, FinalizeSignedDocumentService $finalizer)
    {
        $user = $request->user();

        if (!$user->can('approve reservation form')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $booking = Booking::with(['customerInfos', 'reservationForm'])->findOrFail($bookingId);
        $rf = $booking->reservationForm()->firstOrFail();

        // ✅ block if signatures incomplete OR not finalized
        $config = new FinalizeSignedDocumentConfig(
            type: DocumentType::RF,
            view: 'pdf.reservation_form',
            signedDir: 'reservation_forms/signed',
            filePrefix: 'RF_SIGNED_FINAL_',
        );

        $signedPath = $finalizer->finalizeBookingIfComplete(
            documentable: $rf,
            bookingId: $rf->booking_id,
            cfg: $config
        );

        if (empty($signedPath)) {
            return response()->json([
                'error' => 'Cannot approve Reservation Form: required signatures are incomplete.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rf->refresh();

        if (empty($rf->signed_file_path)) {
            $rf->forceFill(['signed_file_path' => $signedPath])->save();
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
        $docType = 'Reservation Form (RF)';
        $subject = 'Your Reservation Form (RF)';
        $view = 'emails.rf_spa_final';

        foreach ($booking->customerInfos as $customer) {
            if (empty($customer->email)) continue;

            Mail::to($customer->email)->queue(
                new RFSPAMail($booking, $customer, $fileName, $rf->signed_file_path, $docType, $subject, $view)
            );
        }

        return response()->json(['message' => 'Reservation form has been approved and sent to customer(s).'], Response::HTTP_OK);
    }
}
