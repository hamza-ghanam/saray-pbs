<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DldDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class DldDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/bookings/{booking}/dld",
     *     summary="Upload a DLD document for a booking and mark the unit as Sold",
     *     operationId="uploadBookingDld",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         description="ID of the booking to upload DLD for",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="dld_document",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file of the DLD document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="DLD uploaded and booking & unit status updated to Sold",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="DLD uploaded and persisted; booking and unit are now Sold."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden — user lacks permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed or unit not in Completed status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Cannot upload DLD unless the unit is in Completed status.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict — DLD already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="A DLD document has already been uploaded for this booking.")
     *         )
     *     )
     * )
     */
    public function store(Request $request, Booking $booking)
    {
        $user = $request->user();
        Log::info("User {$user->id} is uploading a DLD for booking ID: {$booking->id}");

        if (!$user->can('upload DLD document')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (! $booking->unit || $booking->unit->status !== 'Completed' || $booking->status !== 'Completed') {
            return response()->json([
                'error' => 'Cannot upload DLD unless the unit is in Completed status.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if DLD exists, reject the request
        if ($booking->dldDocument) {
            return response()->json([
                'error' => 'A DLD document has already been uploaded for this booking.'
            ], Response::HTTP_CONFLICT);
        }

        $validator = Validator::make($request->all(), [
            'dld_document' => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->file('dld_document');
        $timestamp = now()->format('Ymd_His');
        $fileName = "dld_booking_{$booking->id}_{$timestamp}.pdf";
        $filePath = 'dld_docs/' . $fileName;

        DB::transaction(function() use ($booking, $file, $fileName, $user, $filePath) {
            // 3. Store the file (public disk → storage/app/public/dld_docs)
            Storage::disk('local')->putFileAs('dld_docs', $file, $fileName);

            // 4. Persist DldDocument
            DldDocument::create([
                'booking_id'  => $booking->id,
                'file_path'   => $filePath,
                'uploaded_by' => $user->id,
            ]);

            // 5. Update booking status
            $booking->update([
                'status' => 'Booked',
            ]);

            // 6. Update associated unit
            $booking->unit->update([
                'status'            => 'Sold',
                'status_changed_at' => now(),
            ]);
        });

        $booking->load('dldDocument');
        $booking->load('dldDocument.uploader');

        return response()->json([
            'message' => 'DLD uploaded and persisted; booking is done and unit is now Sold!',
            'booking' => $booking,
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     */
    public function show(DldDocument $dldDocument)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DldDocument $dldDocument)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DldDocument $dldDocument)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DldDocument $dldDocument)
    {
        //
    }
}
