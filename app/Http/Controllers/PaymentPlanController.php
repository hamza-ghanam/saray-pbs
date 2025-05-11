<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentPlanRequest;
use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Unit;
use App\Services\PaymentPlanService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *     schema="Installment",
 *     type="object",
 *     title="Installment",
 *     required={"payment_plan_id", "description", "percentage", "date", "amount"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="payment_plan_id", type="integer", example=5),
 *     @OA\Property(property="description", type="string", example="Booking Installment"),
 *     @OA\Property(property="percentage", type="number", format="float", example=20),
 *     @OA\Property(property="date", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="amount", type="number", format="float", example=293173.12)
 * )
 */

class PaymentPlanController extends Controller
{
    /**
     * Get a payment plan with its installments.
     *
     * @OA\Get(
     *     path="/payment-plans/{id}",
     *     summary="Get a payment plan with its installments",
     *     tags={"PaymentPlan"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the payment plan",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment plan retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_plan", ref="#/components/schemas/PaymentPlan")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Payment plan not found")
     * )
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->can('show payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::with('installments')->findOrFail($id);
        return response()->json(['payment_plan' => $paymentPlan], Response::HTTP_OK);
    }

    /**
     * Get all payment plans for a given unit with their installments.
     *
     * @OA\Get(
     *     path="/units/{unit_id}/payment-plans",
     *     summary="Get all payment plans for a unit",
     *     tags={"PaymentPlan"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="path",
     *         description="ID of the unit",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment plans retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="payment_plans",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PaymentPlan")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No payment plans found for this unit")
     * )
     *
     * @param int $unit_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlansForUnit(Request $request, $unit_id)
    {
        $user = $request->user();
        if (!$user->can('show payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Optionally, you may check if the unit exists.
        $unit = Unit::findOrFail($unit_id);

        $paymentPlans = PaymentPlan::with('installments')->where('unit_id', $unit->id)->get();
        return response()->json(['payment_plans' => $paymentPlans], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/units/{unit}/payment-plans",
     *     summary="Persist a payment‐plan definition (blocks) for a unit",
     *     tags={"PaymentPlans"},
     *
     *     @OA\Parameter(
     *         name="unit",
     *         in="path",
     *         description="ID of the unit",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","dld_fee_percentage","admin_fee","EOI","blocks"},
     *             @OA\Property(property="name", type="string", example="Custom Plan A"),
     *             @OA\Property(property="dld_fee_percentage", type="number", format="float", example=2),
     *             @OA\Property(property="admin_fee", type="number", format="float", example=500),
     *             @OA\Property(property="EOI", type="number", format="float", example=10000),
     *             @OA\Property(
     *                 property="blocks",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"type","description","percentage"},
     *                     @OA\Property(property="type", type="string", enum={"single","repeat"}, example="single"),
     *                     @OA\Property(property="description", type="string", example="Booking Deposit"),
     *                     @OA\Property(property="percentage", type="number", format="float", example=10),
     *                     @OA\Property(property="date", type="string", format="date", nullable=true, example="2025-06-01"),
     *                     @OA\Property(
     *                         property="offset",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="months", type="integer", example=0),
     *                         @OA\Property(property="years", type="integer", example=0)
     *                     ),
     *                     @OA\Property(
     *                         property="start_offset",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="months", type="integer", example=2),
     *                         @OA\Property(property="years", type="integer", example=0)
     *                     ),
     *                     @OA\Property(
     *                         property="frequency",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="months", type="integer", example=1),
     *                         @OA\Property(property="years", type="integer", example=0)
     *                     ),
     *                     @OA\Property(property="count", type="integer", nullable=true, example=30)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Payment plan definition stored",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="unit_id", type="integer", example=15),
     *             @OA\Property(property="name", type="string", example="Custom Plan A"),
     *             @OA\Property(property="blocks", type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
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
     *     )
     * )
     */
    public function store(
        StorePaymentPlanRequest $request,
        PaymentPlanService $builder
    ) {
        // 1) Grab the validated data
        $data = $request->validated();

        // 3) Create the plan header _and_ persist the blocks JSON
        $plan = $builder->createFromDefinition([
            'name'               => $data['name'],
            'dld_fee_percentage' => $data['dld_fee_percentage'],
            'admin_fee'          => $data['admin_fee'],
            'EOI'                => $data['EOI'],
            'blocks'             => $data['blocks'],   // <— store the blocks definition
        ]);

        // 4) Return the saved plan (including the blocks field)
        return response()->json(
            $plan->fresh(),               // reload to include any casts/defaults
            Response::HTTP_CREATED
        );
    }

    public function storeOLD(Request $request)
    {
        $user = $request->user();
        if (!$user->can('add payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        Log::info("Creating custom Payment Plan with data: " . json_encode($request->all()));

        $data = $this->validatePaymentPlanData($request);
        $priceData = $this->computePriceData($data);

        // Create Payment Plan record.
        $paymentPlan = PaymentPlan::create([
            'unit_id'                     => $data['unit_id'],
            'name'                        => $data['name'],
            'dld_fee_percentage'          => $data['dld_fee_percentage'],
            'dld_fee'                     => $priceData['dldFee'],
            'admin_fee'                   => $data['admin_fee'],
            'EOI'                         => $data['EOI'],
            'booking_percentage'          => $data['booking_percentage'],
            'handover_percentage'         => $data['handover_percentage'],
            'construction_percentage'     => $priceData['constructionPercentage'],
            'first_construction_installment_date' => Carbon::parse($data['first_construction_installment_date'])->toDateString(),
        ]);

        // 1. Booking Installment:
        // Formula: (effectivePrice * booking_percentage/100)
        //          + (effectivePrice * dld_fee_percentage/100)
        //          + admin_fee - EOI
        $this->calculateInstallments($priceData, $data, $paymentPlan);

        return response()->json([
            'payment_plan' => $paymentPlan,
            'installments' => $paymentPlan->installments,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing custom payment plan.
     *
     * @OA\Put(
     *     path="/payment-plans/{id}",
     *     summary="Update a custom payment plan",
     *     tags={"PaymentPlan"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the payment plan to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unit_id", "name", "dld_fee_percentage", "admin_fee", "EOI", "booking_percentage", "handover_percentage", "first_construction_installment_date"},
     *             @OA\Property(property="unit_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Custom 60/40 Plan Updated"),
     *             @OA\Property(property="dld_fee_percentage", type="number", format="float", example=4),
     *             @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
     *             @OA\Property(property="EOI", type="number", format="float", example=100000.00),
     *             @OA\Property(property="booking_percentage", type="number", format="float", example=20),
     *             @OA\Property(property="handover_percentage", type="number", format="float", example=40),
     *             @OA\Property(property="first_construction_installment_date", type="string", format="date", example="2025-04-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment plan updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PaymentPlan")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->can('edit payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);

        $data = $this->validatePaymentPlanData($request);
        $priceData = $this->computePriceData($data);

        $paymentPlan->update([
            'unit_id'                     => $data['unit_id'],
            'name'                        => $data['name'],
            'dld_fee_percentage'          => $data['dld_fee_percentage'],
            'dld_fee'                     => $priceData['dldFee'],
            'admin_fee'                   => $data['admin_fee'],
            'EOI'                         => $data['EOI'],
            'booking_percentage'          => $data['booking_percentage'],
            'handover_percentage'         => $data['handover_percentage'],
            'construction_percentage'     => $priceData['constructionPercentage'],
            'first_construction_installment_date' => Carbon::parse($data['first_construction_installment_date'])->toDateString(),
        ]);

        // Delete existing installments.
        $paymentPlan->installments()->delete();

        // Recalculate installments.
        // 1. Booking Installment:
        $this->calculateInstallments($priceData, $data, $paymentPlan);

        return response()->json([
            'payment_plan' => $paymentPlan->fresh('installments'),
        ], Response::HTTP_OK);
    }

    /**
     * Delete a payment plan and its installments.
     *
     * @OA\Delete(
     *     path="/payment-plans/{id}",
     *     summary="Delete a payment plan",
     *     tags={"PaymentPlan"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the payment plan to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Payment plan deleted successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->can('delete payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);
        $paymentPlan->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function validatePaymentPlanData(Request $request): array
    {
        $rules = [
            'unit_id'                     => 'required|exists:units,id',
            'name'                        => 'required|string|max:255',
            'dld_fee_percentage'          => 'required|numeric',
            'admin_fee'                   => 'required|numeric',
            'EOI'                         => 'required|numeric',
            'booking_percentage'          => 'required|numeric|min:0|max:100',
            'handover_percentage'         => 'required|numeric|min:0|max:100',
            'first_construction_installment_date' => 'required|date',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $validator->validated();
    }

    private function computePriceData(array $data): array
    {
        $unit = Unit::findOrFail($data['unit_id']);
        $sellingPrice = $unit->price;
        $effectivePrice = $sellingPrice;
        $constructionPercentage = 100 - ($data['booking_percentage'] + $data['handover_percentage']);
        $dldFee = $effectivePrice * ($data['dld_fee_percentage'] / 100);

        return compact('unit', 'sellingPrice', 'effectivePrice', 'constructionPercentage', 'dldFee');
    }

    /**
     * @param array $priceData
     * @param array $data
     * @param $paymentPlan
     * @return void
     */
    private function calculateInstallments(array $priceData, array $data, PaymentPlan $paymentPlan): void
    {
        $bookingAmount = ($priceData['effectivePrice'] * ($data['booking_percentage'] / 100))
            + $priceData['dldFee']
            + $data['admin_fee']
            - $data['EOI'];

        Installment::create([
            'payment_plan_id' => $paymentPlan->id,
            'description' => 'Booking Installment',
            'percentage' => $data['booking_percentage'],
            'date' => Carbon::now()->toDateString(),
            'amount' => round($bookingAmount, 2),
        ]);

        // 2. Handover Installment:
        $completionDate = Carbon::parse($priceData['unit']->completion_date);
        $handoverAmount = $priceData['effectivePrice'] * ($data['handover_percentage'] / 100);

        Installment::create([
            'payment_plan_id' => $paymentPlan->id,
            'description' => 'Handover Installment',
            'percentage' => $data['handover_percentage'],
            'date' => $completionDate->toDateString(),
            'amount' => round($handoverAmount, 2),
        ]);

        // 3. Construction Installments:
        $constructionTotal = $priceData['effectivePrice'] * ($priceData['constructionPercentage'] / 100);
        $firstConstructionDate = Carbon::parse($data['first_construction_installment_date']);
        $months = $firstConstructionDate->diffInMonths($completionDate) + 1;
        $monthlyAmount = $constructionTotal / $months;

        for ($i = 0; $i < $months; $i++) {
            $installmentDate = $firstConstructionDate->copy()->addMonths($i);

            Installment::create([
                'payment_plan_id' => $paymentPlan->id,
                'description' => 'Construction Installment ' . ($i + 1),
                'percentage' => round($priceData['constructionPercentage'] / $months, 2),
                'date' => $installmentDate->toDateString(),
                'amount' => round($monthlyAmount, 2),
            ]);
        }
    }
}
