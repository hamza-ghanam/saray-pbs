<?php

namespace App\Http\Controllers;

use App\Models\CustomerInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomerInfoController extends Controller
{
    /**
     * Get a paginated list of all customers with their booking and unit/building info.
     *
     * @OA\Get(
     *     path="/customers",
     *     summary="List all customers with related booking, unit, and building",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of customers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=85),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=101),
     *                     @OA\Property(property="name", type="string", example="John Smith"),
     *                     @OA\Property(property="passport_number", type="string", example="N001234567"),
     *                     @OA\Property(property="birth_date", type="string", format="date", example="1992-02-05"),
     *                     @OA\Property(property="gender", type="string", example="Male"),
     *                     @OA\Property(property="nationality", type="string", example="Syrian Arab Republic"),
     *
     *                     @OA\Property(
     *                         property="booking",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=42),
     *                         @OA\Property(property="status", type="string", example="Pre-Booked"),
     *
     *                         @OA\Property(
     *                             property="unit",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=12),
     *                             @OA\Property(property="unit_no", type="string", example="A-203"),
     *                             @OA\Property(
     *                                 property="building",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=3),
     *                                 @OA\Property(property="name", type="string", example="Sunrise Tower"),
     *                                 @OA\Property(property="location", type="string", example="Downtown")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to list customers info");

        if (!$user->can('view customer')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $limit = min((int)$request->get('limit', 10), 100);
        $customers = CustomerInfo::with('booking.unit.building')->paginate($limit);

        return response()->json($customers, Response::HTTP_OK);
    }

    /**
     * Get a specific customer's full details, including their booking, unit, and building.
     *
     * @OA\Get(
     *     path="/customers/{id}",
     *     summary="Retrieve a customer and their associated booking, unit, and building",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the customer",
     *         required=true,
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=101),
     *             @OA\Property(property="name", type="string", example="John Smith"),
     *             @OA\Property(property="passport_number", type="string", example="N001234567"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1992-02-05"),
     *             @OA\Property(property="gender", type="string", example="Male"),
     *             @OA\Property(property="nationality", type="string", example="Syrian Arab Republic"),
     *
     *             @OA\Property(
     *                 property="booking",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="status", type="string", example="Pre-Booked"),
     *                 @OA\Property(property="discount", type="number", format="float", example=5),
     *
     *                 @OA\Property(
     *                     property="unit",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="unit_no", type="string", example="A-203"),
     *
     *                     @OA\Property(
     *                         property="building",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Sunrise Tower"),
     *                         @OA\Property(property="location", type="string", example="Downtown")
     *                     )
     *                 ),
     *
     *                 @OA\Property(
     *                     property="payment_plan",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Standard Plan")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="approvals",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Approval")
     *                 ),
     *                 @OA\Property(
     *                     property="spa",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="status", type="string", example="Pending"),
     *                     @OA\Property(
     *                         property="approvals",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Approval")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="reservation_form",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="status", type="string", example="Signed"),
     *                     @OA\Property(
     *                         property="approvals",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Approval")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to list customers info");

        if (!$user->can('view customer')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $customer = CustomerInfo::with([
            'booking.unit.building',
            'booking.paymentPlan',
            'booking.installments',
            'booking.approvals',
            'booking.spa.approvals',
            'booking.reservationForm.approvals'
        ])->find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($customer, Response::HTTP_OK);
    }

}


