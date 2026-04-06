<?php

namespace App\Http\Controllers;

use App\Actions\Signature\CustomerSignatureUploadConfig;
use App\Actions\Signature\FinalizeConfig;
use App\Actions\Signature\FinalizeSignedDocumentService;
use App\Actions\Signature\SendForSignatureAction;
use App\Actions\Signature\SignatureSendConfig;
use App\Actions\Signature\SignatureSubmitConfig;
use App\Actions\Signature\SubmitSignatureAction;
use App\Actions\Signature\UploadCustomerSignatureAction;
use App\Enums\DocumentType;
use App\Mail\RFSPAMail;
use App\Mail\SalesPurchaseAgreementMail;
use App\Models\Approval;
use App\Models\Booking;
use App\Models\SPA;
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

// Your SPA model

// dompdf

class SpaController extends Controller
{
    protected PaymentPlanService $paymentPlanService;

    public function __construct(PaymentPlanService $paymentPlanService)
    {
        $this->paymentPlanService = $paymentPlanService;
    }

    /**
     * Generate or download an SPA PDF for a booking.
     *
     * If an SPA already exists (and its PDF file is present on disk), the existing PDF is emailed to the customer(s) (HTTP 200).
     * Otherwise, a new PDF is generated, stored, and emailed (HTTP 201).
     *
     * @OA\Get(
     *     path="/bookings/{bookingId}/spa",
     *     summary="Generate or download an SPA PDF for a booking",
     *     tags={"Bookings/SPA"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking for which to generate or retrieve the SPA",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="New SPA PDF generated and streamed successfully",
     *         @OA\MediaType(mediaType="application/pdf")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (no permission to generate SPA)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found or existing SPA file missing on disk"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation or business-rule error (e.g., unit/booking status invalid)"
     *     )
     * )
     */
    public function generate(Request $request, $bookingId, PdfService $pdfService)
    {
        $user = $request->user();

        // Check user permissions (Sales or Broker can generate an SPA)
        if (!$user->can('generate spa')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // 1. Retrieve the booking
        $booking = Booking::with('unit')->find($bookingId);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        Log::info("User {$user->id} generated an SPA for booking {$booking->id}");

        $companySignedAt = now()->copy();

        // 2. Check the booking and unit status logic
        //    only generate SPA if:
        //      - the unit has status "Booked"
        //      - the booking status is "SPA Pending" or "Booked"
        $validBookingStatuses = [Booking::STATUS_SPA_PENDING, Booking::STATUS_BOOKED];
        if ($booking->unit->status !== Unit::STATUS_BOOKED ||
            !in_array($booking->status, $validBookingStatuses)) {
            return response()->json([
                'error' => 'Cannot generate SPA unless unit is "Booked" and booking is in "SPA Pending" or "Booked" status.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {

            // 3. Ensure only one SPA per booking
            /*
            $existingSPA = SPA::where('booking_id', $booking->id)->first();
            if ($existingSPA) {
                // If file exists, return existing
                if (Storage::disk('local')->exists($existingSPA->file_path)) {
                    foreach ($booking->customerInfos as $customer) {
                        Mail::to($customer->email)->queue(new SalesPurchaseAgreementMail($booking, $fileName));
                    }

                    return response()->json(['message' => 'SPA emailed to customer(s) successfully.'], Response::HTTP_OK);
                } else {
                    return response()->json([
                        'error' => 'Existing SPA file not found on disk.'
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

            $spaData = [
                'booking' => $booking,
                'customerInfos' => $booking->customerInfos,
                'paymentPlan' => $booking->paymentPlan,
                'installments' => $booking->installments,
                'unit' => $booking->unit,
                'companySignedAt' => $companySignedAt,
            ];

            /*
            // 4. Generate the PDF (using your Blade view, e.g. 'pdf.spa')
            $pdf = PDF::loadView('pdf.spa', [
                'booking' => $booking,
                'customerInfos' => $booking->customerInfos,
                'paymentPlan' => $booking->paymentPlan,
                'unit' => $booking->unit,
            ]);
            */

            // New SPA template || 12/9/2025
            // 5. Store the PDF file on disk
            $fileName = 'SPA_' . $booking->id . '.pdf';
            $filePath = 'spa_forms/' . $fileName;
            $pdfContent = $pdfService->store('pdf.spa2', $spaData, $filePath);

            $existingSPA = SPA::where('booking_id', $booking->id)->first();
            if ($existingSPA) {
                $existingSPA->update([
                    'file_path' => $filePath,
                    'status' => 'Pending',
                    'company_signed_at' => $companySignedAt,
                ]);
            } else {
                // 6. Create a new SPA record with status = "Pending"
                SPA::create([
                    'booking_id' => $booking->id,
                    'file_path' => $filePath,
                    'status' => 'Pending',
                    'company_signed_at' => $companySignedAt,
                ]);
            }

            DB::commit();

            /*
            foreach ($booking->customerInfos as $customer) {
                Mail::to($customer->email)->queue(new SalesPurchaseAgreementMail($booking, $fileName));
            }
            */

            return response($pdfContent, Response::HTTP_CREATED, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        } catch (\Exception $ex) {
            DB::rollback();
            Log::error("SPA Booking ID: {$booking->id} Generation Error: " . $ex->getMessage());
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send SPA signing links to booking customers.
     *
     * - Requires authenticated user with proper permission.
     * - If user has role "Sales", they can only send for signature for bookings they created.
     * - Booking must be in status "SPA Pending".
     * - SPA must exist and must have a generated PDF (file_path).
     * - Sends signing link(s) to recipients (customerInfos where requires_signature=true).
     * - If a recipient already signed the document, no new link is sent for that recipient.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/spa/send-for-signature",
     *     summary="Send SPA signing link(s)",
     *     tags={"Bookings/SPA"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking whose SPA will be sent for signature",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="SPA signing links processed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="SPA signing link(s) processed successfully."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (no permission to send SPA for signature)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="SPA document not found for this booking",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="SPA not found for this booking. Generate SPA first.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation or business rule error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="SPA PDF is missing. Generate SPA again.")
     *         )
     *     )
     * )
     */
    public function sendForSignature(Request $request, int $bookingId, SendForSignatureAction $action)
    {
        return $action->handle($request, $bookingId, new SignatureSendConfig(
            permission: 'upload signed spa',
            requiredBookingStatus: Booking::STATUS_SPA_PENDING,
            documentModelClass: SPA::class,
            documentTypeValue: DocumentType::SPA->value,
            missingDocMessage: 'SPA not found for this booking. Generate SPA first.',
            missingPdfMessage: 'SPA PDF is missing. Generate SPA again.',
            successMessage: 'SPA signing link(s) sent successfully.',
            finalizedStatuses: ['Signed', 'Approved'],
            alreadyFinalizedMessage: 'Document already finalized.',
        ));
    }

    /**
     * Submit a signature for a Sales Purchase Agreement (SPA) using a one-time token.
     *
     * - Public endpoint (no auth) protected by one-time token.
     * - Accepts a PNG signature as base64 (either raw base64 or data URL).
     * - Stores the signature image and marks the signing link as used (status -> expired).
     * - Stores signature_source = "customer_self" (self-service signing by the customer).
     * - If all required signers have completed, the system finalises the SPA (generates final signed PDF).
     *
     * @OA\Post(
     *     path="/bookings/spa/{token}/sign",
     *     summary="Submit SPA signature (base64 PNG)",
     *     tags={"Bookings/SPA"},
     *     description="Public self-service signing flow. The customer submits their SPA signature via a one-time token. The system stores signature_source as customer_self to distinguish it from staff-assisted uploads, which use staff_uploaded.",
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
     *                 description="True if this submission completed all required signatures and the SPA was finalised.",
     *                 example=false
     *             ),
     *             @OA\Property(property="signature_source", type="string", example="customer_self", description="Signature source value.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token / link not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid or expired signing link.")
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
     *         response=409,
     *         description="Signature already submitted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="This document has already been signed.")
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
    public function submitSignature(Request $request, string $token, SubmitSignatureAction $action, FinalizeSignedDocumentService $finalizer){
        // Customer self-service digital signature flow (signature_source should be customer_self inside the submit action/service).
        $cfg = new SignatureSubmitConfig(
            type: DocumentType::SPA,
            expectedDocumentClass: SPA::class,
            signatureDir: 'signatures/spa',
            invalidDocMessage: 'Invalid document type for this endpoint.',
        );

        $config = new FinalizeConfig(
            type: DocumentType::SPA,
            view: 'pdf.spa2',
            signedDir: 'spa_forms/signed',
            filePrefix: 'SP_SIGNED_FINAL_',
        );

        return $action->handle(
            $request,
            $token,
            $cfg,
            finalize: function ($spa) use ($finalizer, $config) {
                $signedPath = $finalizer->finalizeBookingIfComplete($spa, $spa->booking_id, $config);
                return !empty($signedPath);
            }
        );
    }


    /**
     * Upload a customer signature image for an SPA on behalf of the customer.
     *
     * This simulates the normal submit-signature flow but without sending any email.
     * It is used when the customer cannot sign through the emailed link and instead
     * provides their signature image to staff, who uploads it through the system.
     *
     * Behaviour:
     * - Requires authenticated user with "upload final spa" permission.
     * - SPA must exist and be strictly in Pending status.
     * - customer_id must belong to this booking and must require signature.
     * - Reuses/refreshes the signer record used for the signing flow without sending email.
     * - Stores the uploaded signature image exactly like digital submitSignature flow.
     * - Marks the link as signed and records `signature_source = staff_uploaded`.
     * - Attempts finalisation using the same FinalizeSignedDocumentService used by submitSignature.
     * - Triggers finalisation immediately if this upload completes all required signatures.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/spa/upload-signature",
     *     summary="Upload customer signature for SPA (staff-assisted)",
     *     tags={"Bookings/SPA"},
     *     security={{"sanctum":{}}},
     *     description="This endpoint allows staff to upload a customer's signature image instead of using the email-based signing flow. It simulates the same behaviour as submitSignature, but without sending email. The signing record uses signature_source staff_uploaded, while the normal self-service submitSignature flow uses customer_self. If this upload completes all required signatures, the SPA is finalized immediately.",
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
     *             @OA\Property(
     *                 property="customer_id",
     *                 type="integer",
     *                 example=55,
     *                 description="CustomerInfo ID of the signer (must belong to this booking and require signature)"
     *             ),
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
     *             @OA\Property(property="finalized", type="boolean", example=false, description="True if this upload completed all required signatures and the SPA was finalized immediately."),
     *             @OA\Property(property="signing_link_id", type="integer", example=1234),
     *             @OA\Property(property="signature_source", type="string", example="staff_uploaded", description="Signature source value.")
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
     *         description="Booking or SPA not found",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Booking not found")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="SPA not found for this booking. Generate SPA first.")
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
     *                     @OA\Property(property="error", type="string", example="Customer signature upload is only allowed when SPA status is Pending.")
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
            permission: 'upload final spa',
            bookingRelation: 'spa',
            expectedDocumentClass: SPA::class,
            type: DocumentType::SPA,
            signatureDir: 'signatures/spa',
            filePrefix: 'spa_signature',
            documentNotFoundMessage: 'SPA not found for this booking. Generate SPA first.',
            invalidStatusMessage: 'Customer signature upload is only allowed when SPA status is Pending.',
        );

        $finalizeCfg = new FinalizeConfig(
            type: DocumentType::SPA,
            view: 'pdf.spa2',
            signedDir: 'spa_forms/signed',
            filePrefix: 'SP_SIGNED_FINAL_',
        );

        return $action->handle(
            request: $request,
            bookingId: $bookingId,
            cfg: $cfg,
            finalize: fn ($spa) => !empty(
            $finalizer->finalizeBookingIfComplete(
                documentable: $spa,
                bookingId: $spa->booking_id,
                cfg: $finalizeCfg
            )
            )
        );
    }

    public function uploadSigned(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is uploading a signed SPA for SPA ID: {$id}");

        if (!$user->can('upload final spa')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // 1. Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'signed_spa' => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Retrieve the SPA record
        $spa = Booking::findOrFail($id)
            ->spa()
            ->firstOrFail();

        $role = $user->getRoleNames()->first();
        if ($spa->status !== 'Pending' && !in_array($role, ['CEO', 'System Maintenance'])) {
            return response()->json(
                ['error' => 'The signed SPA was already uploaded'],
                Response::HTTP_CONFLICT
            );
        }

        // 3. Store the signed SPA file
        $file = $request->file('signed_spa');
        $fileName = 'SPA_' . $spa->booking_id . '_signed.' . $file->getClientOriginalExtension();
        $filePath = 'spa_forms/' . $fileName;
        Storage::disk('local')->putFileAs('spa_forms', $file, $fileName);

        // 4. Update the SPA record
        $spa->update([
            'status' => 'Signed',
            'signed_at' => now(),
            'signed_file_path' => $filePath,
        ]);

        return response()->json(['message' => 'Signed SPA has been successfully uploaded'], Response::HTTP_OK);
    }

    /**
     * Approve a signed SPA.
     *
     * Sets the SPA status to "Approved", updates the related Booking and Unit to "Sold",
     * and records an Approval entry.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/spa/approve",
     *     summary="Approve a signed SPA for a booking",
     *     tags={"Bookings/SPA"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking whose SPA will be approved",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SPA approved and Unit marked as Sold",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="SPA has been approved! Waiting for DLD document"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks 'approve spa' permission"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SPA not found for the given booking"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot approve unless the SPA is Signed or other validation error"
     *     )
     * )
     */
    public function approve(Request $request, $id, FinalizeSignedDocumentService $finalizer)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to approve SPA ID: {$id}");

        if (!$user->can('approve spa')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // 1. Retrieve the SPA record
        $spa = Booking::findOrFail($id)
            ->spa()
            ->firstOrFail();

        // ✅ block if signatures incomplete OR not finalized
        $config = new FinalizeConfig(
            type: DocumentType::SPA,
            view: 'pdf.spa2',
            signedDir: 'spa_forms/signed',
            filePrefix: 'SPA_SIGNED_FINAL_',
        );

        $signedPath = $finalizer->finalizeBookingIfComplete(
            documentable: $spa,
            bookingId: $spa->booking_id,
            cfg: $config
        );

        if (empty($signedPath)) {
            return response()->json([
                'error' => 'Cannot approve SPA: required signatures are incomplete.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Ensure we have the latest signed_file_path persisted by the finalizer
        $spa->refresh();

        if (empty($spa->signed_file_path)) {
            $spa->forceFill(['signed_file_path' => $signedPath])->save();
        }


        // 2. Must be "Signed" to approve
        if ($spa->status !== 'Signed') {
            return response()->json([
                'error' => 'Cannot approve an SPA unless it is Signed.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3. Approve the SPA (set status to "Approved")
        $spa->status = 'Approved';
        $spa->save();

        // 4. Change the associated Booking status to "Sold" (final)
        if ($spa->booking) {
            $spa->booking->update([
                'status' => 'Completed',
            ]);

            if ($spa->booking->unit) {
                $spa->booking->unit->update([
                    'status' => 'Completed',
                    'status_changed_at' => now(),
                ]);
            }
        }

        // 5. Make approval
        Approval::create([
            'ref_id' => $spa->id,
            'ref_type' => 'App\Models\SPA',
            'approved_by' => $user->id,
            'approval_type' => $user->getRoleNames()->first(),
            'status' => 'Approved',
        ]);

        $fileName = 'SPA_' . $spa->booking->id . '_' . $spa->booking->unit->unit_no . '_SIGNED.pdf';
        $docType = 'Sales and Purchase Agreement (SPA)';
        $subject = 'Your ' . $docType;
        $view = 'emails.rf_spa_final';

        foreach ($spa->booking->customerInfos as $customer) {
            Mail::to($customer->email)->queue(
                new RFSPAMail($spa->booking, $customer, $fileName, $spa->signed_file_path, $docType, $subject, $view)
            );
        }

        return response()->json(['message' => 'SPA has been approved! Waiting for DLD document.'], Response::HTTP_OK);
    }
}
