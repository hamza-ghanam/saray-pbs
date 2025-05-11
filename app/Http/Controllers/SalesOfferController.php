<?php

namespace App\Http\Controllers;

use App\Models\PaymentPlan;
use App\Models\SalesOffer;
use App\Models\Unit;
use App\Services\PaymentPlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Collection;

class SalesOfferController extends Controller
{
    protected PaymentPlanService $paymentPlanService;

    public function __construct(PaymentPlanService $paymentPlanService)
    {
        $this->paymentPlanService = $paymentPlanService;
    }

    /**
     * Generate a Sales Offer PDF on the fly.
     *
     * @OA\Post(
     *     path="/sales-offers/generate",
     *     summary="Generate a sales offer PDF on the fly",
     *     tags={"SalesOffers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unit_id"},
     *             @OA\Property(
     *                 property="unit_id",
     *                 type="integer",
     *                 format="int64",
     *                 description="ID of the unit to base the offer on",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="payment_plan_ids",
     *                 type="array",
     *                 description="Optional list of payment plan IDs to include in this offer",
     *                 @OA\Items(type="integer", format="int64", example=3)
     *             ),
     *             @OA\Property(
     *                 property="discount",
     *                 type="number",
     *                 format="float",
     *                 description="Optional discount percentage to apply to the offer price",
     *                 example=5
     *             ),
     *             @OA\Property(
     *                 property="notes",
     *                 type="string",
     *                 description="Optional free-text notes for this offer",
     *                 example="Special end-of-year promotion"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales offer PDF streamed successfully",
     *         @OA\MediaType(mediaType="application/pdf")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Schema(type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (no permission to generate sales offer)"
     *     )
     * )
     */
    public function generate(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is generating a sales offer.");

        // Check user permissions (Sales or Broker can generate a sales offer)
        if (!$user->can('generate sales offer')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Validate incoming request data.
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'notes' => 'nullable|string',
            'payment_plan_ids' => 'nullable|array',
            'payment_plan_ids.*' => 'integer|exists:payment_plans,id',
            'discount' => 'nullable|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Retrieve the unit along with its building.
        $unit = Unit::with('building')->findOrFail($data['unit_id']);

        // Start a fresh query on PaymentPlan
        $plansQuery = PaymentPlan::query();

        if (!empty($data['payment_plan_ids'])) {
            // user supplied specific plan IDs
            $plansQuery->whereIn('id', $data['payment_plan_ids']);
        } else {
            // fallback to the globally-defined default plan(s)
            $plansQuery->where('isDefault', true);
        }

        $paymentPlans = $plansQuery->get();

        $unit->load('building');

        $basePrice = $unit->price;
        $discountPct = $request->input('discount', 0);
        $offerPrice   = $discountPct > 0
            ? round($basePrice * (1 - $discountPct / 100), 2)
            : $basePrice;

        // 4) Generate installments for each
        $allPlans = $paymentPlans->map(function (PaymentPlan $plan) use ($offerPrice, $discountPct, $unit) {
            $insts = $this->paymentPlanService->generateInstallments($unit, $plan, $discountPct);

            // override the relationship in-memory
            $plan->setRelation('installments', $insts);
            $plan->dld_fee = round($offerPrice * ($plan->dld_fee_percentage / 100), 2);

            return $plan;
        });

        $salesOffer = SalesOffer::create([
            'unit_id'           => $unit->id,
            'generated_by_id'   => auth()->id(),
            'offer_date'        => now(),
            'offer_price'       => $offerPrice,
            'discount'          => $discountPct,
            'notes'             => $request->input('notes', null),
        ]);

        // Prepare the data array for the PDF view.
        $salesOfferData = [
            'salesOffer'    => $salesOffer,
            'unit'          => $unit,
            'notes'         => $data['notes'] ?? null,
            'paymentPlans'  => $allPlans,
            'generated_by'  => $user,
        ];

        // Generate a PDF from the view 'pdf.sales_offer'.
        // Ensure you have created this view which accepts the provided data.
        $pdf = PDF::loadView('pdf.sales_offer', $salesOfferData);

        // Stream the PDF file directly to the browser.
        return $pdf->stream('sales_offer.pdf');
    }
}
