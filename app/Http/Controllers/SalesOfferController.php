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
     * Generate and download a Sales Offer PDF for a unit.
     *
     * @OA\Post(
     *     path="/api/sales-offers/generate",
     *     summary="Generate and stream a Sales Offer PDF",
     *     tags={"SalesOffers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unit_id"},
     *             @OA\Property(property="unit_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Special discount offer", nullable=true),
     *             @OA\Property(
     *                 property="payment_plan_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=3),
     *                 description="Optional list of Payment Plan IDs to include"
     *             ),
     *             @OA\Property(property="discount", type="number", format="float", minimum=0, maximum=100, example=5, description="Optional discount percentage")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales Offer PDF streamed successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(response=422, description="Validation error")
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
