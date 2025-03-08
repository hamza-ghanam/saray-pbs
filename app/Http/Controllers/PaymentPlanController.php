<?php

namespace App\Http\Controllers;

use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Unit;
use Carbon\Carbon;
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
            abort(403, 'Unauthorized');
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
            abort(403, 'Unauthorized');
        }

        // Optionally, you may check if the unit exists.
        $unit = Unit::findOrFail($unit_id);

        $paymentPlans = PaymentPlan::with('installments')->where('unit_id', $unit->id)->get();
        return response()->json(['payment_plans' => $paymentPlans], Response::HTTP_OK);
    }

    /**
     * Store a custom payment plan for a unit.
     *
     * @OA\Post(
     *     path="/payment-plans",
     *     summary="Create a custom payment plan",
     *     tags={"PaymentPlan"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                  "unit_id", "name", "dld_fee_percentage", "admin_fee", "discount", "EOI",
     *                  "booking_percentage", "handover_percentage", "first_construction_installment_date"
     *             },
     *             @OA\Property(property="unit_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Custom 60/40 Plan"),
     *             @OA\Property(property="dld_fee_percentage", type="number", format="float", example=4),
     *             @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
     *             @OA\Property(property="discount", type="number", format="float", example=10, description="Discount as a percentage"),
     *             @OA\Property(property="EOI", type="number", format="float", example=100000.00),
     *             @OA\Property(property="booking_percentage", type="number", format="float", example=20),
     *             @OA\Property(property="handover_percentage", type="number", format="float", example=40),
     *             @OA\Property(property="first_construction_installment_date", type="string", format="date", example="2025-04-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment plan created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_plan", ref="#/components/schemas/PaymentPlan"),
     *             @OA\Property(
     *                 property="installments",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Installment")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->can('add payment plan')) {
            abort(403, 'Unauthorized');
        }

        Log::info("Creating custom Payment Plan with data: " . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'unit_id'                     => 'required|exists:units,id',
            'name'                        => 'required|string|max:255',
            'dld_fee_percentage'          => 'required|numeric',
            'admin_fee'                   => 'required|numeric',
            'discount'                    => 'required|numeric',
            'EOI'                         => 'required|numeric',
            'booking_percentage'          => 'required|numeric|min:0|max:100',
            'handover_percentage'         => 'required|numeric|min:0|max:100',
            'first_construction_installment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Retrieve the unit to get its selling price and completion date.
        $unit = Unit::findOrFail($data['unit_id']);
        $sellingPrice = $unit->price;
        // Apply discount (as a percentage).
        $effectivePrice = $sellingPrice * (1 - ($data['discount'] / 100));
        // Compute construction percentage.
        $constructionPercentage = 100 - ($data['booking_percentage'] + $data['handover_percentage']);

        // Compute the DLD Fee amount
        $dldFee = ($effectivePrice * ($data['dld_fee_percentage'] / 100));

        // Create Payment Plan record.
        $paymentPlan = PaymentPlan::create([
            'unit_id'                     => $data['unit_id'],
            'name'                        => $data['name'],
            'selling_price'               => $sellingPrice,
            'dld_fee_percentage'          => $data['dld_fee_percentage'],
            'dld_fee'                     => $dldFee,
            'admin_fee'                   => $data['admin_fee'],
            'discount'                    => $data['discount'],
            'EOI'                         => $data['EOI'],
            'booking_percentage'          => $data['booking_percentage'],
            'handover_percentage'         => $data['handover_percentage'],
            'construction_percentage'     => $constructionPercentage,
            'first_construction_installment_date' => Carbon::parse($data['first_construction_installment_date'])->toDateString(),
        ]);

        // 1. Booking Installment:
        // Formula: (effectivePrice * booking_percentage/100)
        //          + (effectivePrice * dld_fee_percentage/100)
        //          + admin_fee - EOI
        $bookingAmount = ($effectivePrice * ($data['booking_percentage'] / 100))
            + $dldFee
            + $data['admin_fee']
            - $data['EOI'];

        Installment::create([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => 'Booking Installment',
            'percentage'      => $data['booking_percentage'],
            'date'            => Carbon::now()->toDateString(),
            'amount'          => round($bookingAmount, 2),
        ]);

        // 2. Handover Installment:
        $completionDate = Carbon::parse($unit->completion_date);
        $handoverAmount = $effectivePrice * ($data['handover_percentage'] / 100);

        Installment::create([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => 'Handover Installment',
            'percentage'      => $data['handover_percentage'],
            'date'            => $completionDate->toDateString(),
            'amount'          => round($handoverAmount, 2),
        ]);

        // 3. Construction Installments:
        $constructionTotal = $effectivePrice * ($constructionPercentage / 100);
        $firstConstructionDate = Carbon::parse($data['first_construction_installment_date']);
        $months = $firstConstructionDate->diffInMonths($completionDate) + 1;
        $monthlyAmount = $constructionTotal / $months;

        for ($i = 0; $i < $months; $i++) {
            $installmentDate = $firstConstructionDate->copy()->addMonths($i);

            Installment::create([
                'payment_plan_id' => $paymentPlan->id,
                'description'     => 'Construction Installment ' . ($i + 1),
                'percentage'      => round($constructionPercentage / $months, 2),
                'date'            => $installmentDate->toDateString(),
                'amount'          => round($monthlyAmount, 2),
            ]);
        }

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
     *             required={"unit_id", "name", "dld_fee_percentage", "admin_fee", "discount", "EOI", "booking_percentage", "handover_percentage", "first_construction_installment_date"},
     *             @OA\Property(property="unit_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Custom 60/40 Plan Updated"),
     *             @OA\Property(property="dld_fee_percentage", type="number", format="float", example=4),
     *             @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
     *             @OA\Property(property="discount", type="number", format="float", example=50000.00),
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
        if (!$user->can('update payment plan')) {
            abort(403, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'unit_id'                     => 'required|exists:units,id',
            'name'                        => 'required|string|max:255',
            'dld_fee_percentage'          => 'required|numeric',
            'admin_fee'                   => 'required|numeric',
            'discount'                    => 'required|numeric',
            'EOI'                         => 'required|numeric',
            'booking_percentage'          => 'required|numeric|min:0|max:100',
            'handover_percentage'         => 'required|numeric|min:0|max:100',
            'first_construction_installment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();
        $unit = Unit::findOrFail($data['unit_id']);
        $sellingPrice = $unit->price;
        $effectivePrice = $sellingPrice * (1 - ($data['discount'] / 100));
        $constructionPercentage = 100 - ($data['booking_percentage'] + $data['handover_percentage']);
        $dldFee = ($effectivePrice * ($data['dld_fee_percentage'] / 100));

        $paymentPlan->update([
            'unit_id'                     => $data['unit_id'],
            'name'                        => $data['name'],
            'selling_price'               => $sellingPrice,
            'dld_fee_percentage'          => $data['dld_fee_percentage'],
            'dld_fee'                     => $dldFee,
            'admin_fee'                   => $data['admin_fee'],
            'discount'                    => $data['discount'],
            'EOI'                         => $data['EOI'],
            'booking_percentage'          => $data['booking_percentage'],
            'handover_percentage'         => $data['handover_percentage'],
            'construction_percentage'     => $constructionPercentage,
            'first_construction_installment_date' => Carbon::parse($data['first_construction_installment_date'])->toDateString(),
        ]);

        // Delete existing installments.
        $paymentPlan->installments()->delete();

        // Recalculate installments.
        // 1. Booking Installment:
        $bookingAmount = ($effectivePrice * ($data['booking_percentage'] / 100))
            + $dldFee
            + $data['admin_fee']
            - $data['EOI'];

        Installment::create([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => 'Booking Installment',
            'percentage'      => $data['booking_percentage'],
            'date'            => Carbon::now()->toDateString(),
            'amount'          => round($bookingAmount, 2),
        ]);

        // 2. Handover Installment:
        $completionDate = Carbon::parse($unit->completion_date);
        $handoverAmount = $effectivePrice * ($data['handover_percentage'] / 100);

        Installment::create([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => 'Handover Installment',
            'percentage'      => $data['handover_percentage'],
            'date'            => $completionDate->toDateString(),
            'amount'          => round($handoverAmount, 2),
        ]);

        // 3. Construction Installments:
        $constructionTotal = $effectivePrice * ($constructionPercentage / 100);
        $firstConstructionDate = Carbon::parse($data['first_construction_installment_date']);
        $months = $firstConstructionDate->diffInMonths($completionDate) + 1;
        $monthlyAmount = $constructionTotal / $months;
        for ($i = 0; $i < $months; $i++) {
            $installmentDate = $firstConstructionDate->copy()->addMonths($i);
            Installment::create([
                'payment_plan_id' => $paymentPlan->id,
                'description'     => 'Construction Installment ' . ($i + 1),
                'percentage'      => round($constructionPercentage / $months, 2),
                'date'            => $installmentDate->toDateString(),
                'amount'          => round($monthlyAmount, 2),
            ]);
        }

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
            abort(403, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);
        $paymentPlan->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
