<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeConfig;
use App\Actions\FinalizeSignedDocumentService;
use App\Actions\SendForSignatureAction;
use App\Actions\SignatureSendConfig;
use App\Actions\SignatureSubmitConfig;
use App\Actions\SubmitSignatureAction;
use App\Enums\DocumentType;
use App\Mail\RFSPAMail;
use App\Mail\SalesPurchaseAgreementMail;
use App\Models\Approval;
use App\Models\Booking;
use App\Models\ReservationForm;
use App\Models\SigningLink;
use App\Models\SPA;

// Your SPA model
use App\Models\Unit;
use App\Services\DocumentSignatureService;
use App\Services\PaymentPlanService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

// dompdf
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as MYPDF;
use Mpdf\MpdfException;
use Symfony\Component\HttpFoundation\Response;

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

            $booking->paymentPlan->dld_fee = round($booking->price * ($booking->paymentPlan->dld_fee_percentage / 100), 2);

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

    public function sendForSignature(Request $request, int $bookingId, SendForSignatureAction $action)
    {
        return $action->handle($request, $bookingId, new SignatureSendConfig(
            permission: 'generate spa',
            requiredBookingStatus: Booking::STATUS_SPA_PENDING,
            documentModelClass: SPA::class,
            documentTypeValue: DocumentType::SPA->value,
            missingDocMessage: 'SPA not found for this booking. Generate SPA first.',
            missingPdfMessage: 'SPA PDF is missing. Generate SPA again.',
            successMessage: 'SPA signing link(s) sent successfully.'
        ));
    }

    public function submitSignature(Request $request, string $token, SubmitSignatureAction $action, FinalizeSignedDocumentService $finalizer){
        $cfg = new SignatureSubmitConfig(
            type: DocumentType::SPA,
            expectedDocumentClass: \App\Models\SPA::class,
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
     * Upload a signed SPA file.
     *
     * This endpoint accepts a PDF file for a specific SPA record.
     * If the SPA is no longer "Pending", only users with "CEO" or "System Maintenance" roles
     * can overwrite it. Otherwise, a 409 (Conflict) error is returned.
     *
     * @OA\Post(
     *     path="/bookings/{bookingId}/spa/upload-signed",
     *     summary="Upload a signed SPA file",
     *     tags={"Bookings/SPA"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the SPA to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"signed_spa"},
     *                 @OA\Property(
     *                     property="signed_spa",
     *                     type="string",
     *                     format="binary",
     *                     description="The signed SPA file (PDF, max 2 MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signed SPA uploaded and record updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="spa",
     *                 type="object",
     *                 description="Updated SPA record",
     *                 @OA\Property(property="id",                type="integer",   example=10),
     *                 @OA\Property(property="booking_id",        type="integer",   example=42),
     *                 @OA\Property(property="status",            type="string",    example="Signed"),
     *                 @OA\Property(property="signed_at",         type="string",    format="date-time", example="2025-05-03T12:34:56Z"),
     *                 @OA\Property(property="signed_file_path",  type="string",    example="spa_forms/SPA_42_signed.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden – missing permission to upload final SPA"),
     *     @OA\Response(response=404, description="SPA not found"),
     *     @OA\Response(response=409, description="Conflict – SPA already signed and user lacks override role"),
     *     @OA\Response(response=422, description="Validation error (e.g., no file or wrong MIME type)")
     * )
     */
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
