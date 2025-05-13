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

class PaymentPlanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/payment-plans/{id}",
     *     summary="Retrieve a specific payment plan",
     *     operationId="showPaymentPlan",
     *     tags={"PaymentPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the payment plan to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment plan retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="payment_plan",
     *                 ref="#/components/schemas/PaymentPlan"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden — user lacks permission to view this plan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found — no plan exists with the given ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PaymentPlan] 123")
     *         )
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->can('show payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);
        return response()->json(['payment_plan' => $paymentPlan], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/payment-plans",
     *     summary="Retrieve all payment plans",
     *     operationId="indexPaymentPlans",
     *     tags={"PaymentPlans"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of payment plans",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="payment_plans",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PaymentPlan")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden — user lacks permission to view payment plans",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->can('show payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $paymentPlans = PaymentPlan::all();
        return response()->json(['payment_plans' => $paymentPlans], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/payment-plans",
     *     summary="Persist a payment‐plan definition (blocks)",
     *     tags={"PaymentPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "dld_fee_percentage", "admin_fee", "EOI", "blocks"},
     *             @OA\Property(property="name", type="string", example="Custom Plan A"),
     *             @OA\Property(property="dld_fee_percentage", type="number", format="float", example=2),
     *             @OA\Property(property="admin_fee", type="number", format="float", example=500),
     *             @OA\Property(property="EOI", type="number", format="float", example=10000),
     *             @OA\Property(
     *                 property="blocks",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"type", "description", "percentage"},
     *                     @OA\Property(property="type", type="string", enum={"single", "repeat"}, example="single"),
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
     *     @OA\Response(
     *         response=201,
     *         description="Payment plan definition stored successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="name", type="string", example="Custom Plan A"),
     *             @OA\Property(property="dld_fee_percentage", type="number", format="float", example=2),
     *             @OA\Property(property="admin_fee", type="number", format="float", example=500),
     *             @OA\Property(property="EOI", type="number", format="float", example=10000),
     *             @OA\Property(
     *                 property="blocks",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="single"),
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
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
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


    /**
     * @OA\Delete(
     *     path="/api/payment-plans/{id}",
     *     summary="Delete a payment plan",
     *     tags={"PaymentPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the payment plan to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Payment plan deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden — user lacks permission to delete this plan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found — payment plan does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PaymentPlan] 123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict — cannot delete a plan applied to existing bookings",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cannot delete a payment plan that is applied on existing bookings.")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->can('delete payment plan')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);

        // Reject if there are any bookings tied to this plan
        if ($paymentPlan->bookings()->exists()) {
            return response()->json([
                'error' => 'Cannot delete a payment plan that is applied on existing bookings.'
            ], Response::HTTP_CONFLICT);
        }

        $paymentPlan->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
