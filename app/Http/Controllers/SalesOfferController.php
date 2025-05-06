<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class SalesOfferController extends Controller
{
    /**
     * Generate a Sales Offer PDF on the fly.
     *
     * @OA\Post(
     *     path="/sales-offers/generate",
     *     summary="Generate a sales offer PDF on the fly",
     *     tags={"SalesOffer"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unit_id"},
     *             @OA\Property(property="unit_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Special discount offer"),
     *             @OA\Property(
     *                 property="payment_plan_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=3),
     *                 description="Optional array of Payment Plan IDs to include"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales offer generated successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function generate(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is generating a sales offer.");

        // Check user permissions (Sales or Broker can generate a sales offer)
        if (!$user->can('generate sales offer')) {
            abort(403, 'Unauthorized');
        }

        // Validate incoming request data.
        $validator = Validator::make($request->all(), [
            'unit_id'            => 'required|exists:units,id',
            'notes'              => 'nullable|string',
            'payment_plan_ids'   => 'nullable|array',
            'payment_plan_ids.*' => 'integer|exists:payment_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        /* TODO: Customize the final PDF file */

        // Retrieve the unit along with its building.
        $unit = Unit::with('building')->find($data['unit_id']);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        // Determine which Payment Plans to include.
        if (isset($data['payment_plan_ids'])) {
            // Load only the selected payment plans that belong to this unit.
            $paymentPlans = $unit->paymentPlans()
                ->whereIn('id', $data['payment_plan_ids'])
                ->with('installments')
                ->get();
        } else {
            // Load all payment plans for the unit.
            $paymentPlans = $unit->paymentPlans()->with('installments')->get();
        }

        $unit->load('building');

        // Prepare the data array for the PDF view.
        $salesOfferData = [
            'unit'         => $unit,
            'notes'        => $data['notes'] ?? null,
            'paymentPlans' => $paymentPlans,
            'generated_by' => $user,
            'offer_date'   => now(),
        ];

        // Generate a PDF from the view 'pdf.sales_offer'.
        // Ensure you have created this view which accepts the provided data.
        $pdf = PDF::loadView('pdf.sales_offer', $salesOfferData);

        // Stream the PDF file directly to the browser.
        return $pdf->stream('sales_offer.pdf');
    }
}
