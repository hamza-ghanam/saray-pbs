<?php

namespace App\Http\Controllers;

use App\Models\CustomerInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomerInfoController extends Controller
{
    /**
     * Get a paginated list of all customers with their bookings and unit/building info.
     *
     * @OA\Get(
     *     path="/customers",
     *     summary="List all customers with related bookings, units, and buildings",
     *     description="A customer may be linked to multiple bookings. Each item in `data` includes a `bookings` array instead of a single `booking` object.",
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
     *                     @OA\Property(
     *                         property="bookings",
     *                         type="array",
     *                         description="All bookings this customer is linked to",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=42),
     *                             @OA\Property(property="status", type="string", example="Pre-Booked"),
     *                             @OA\Property(
     *                                 property="pivot",
     *                                 type="object",
     *                                 @OA\Property(property="requires_signature", type="boolean", example=true, description="Whether this customer must sign documents for this booking")
     *                             ),
     *                             @OA\Property(
     *                                 property="unit",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=12),
     *                                 @OA\Property(property="unit_no", type="string", example="A-203"),
     *                                 @OA\Property(
     *                                     property="building",
     *                                     type="object",
     *                                     @OA\Property(property="id", type="integer", example=3),
     *                                     @OA\Property(property="name", type="string", example="Sunrise Tower"),
     *                                     @OA\Property(property="location", type="string", example="Downtown")
     *                                 )
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
        $customers = CustomerInfo::with('bookings.unit.building')->paginate($limit);

        return response()->json($customers, Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/customers/list",
     *     summary="Lightweight customer list for dropdowns",
     *     description="Returns all non-deleted customers with only the fields needed for a dropdown (id, name, email, passport_number). No pagination - intended for select inputs.",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Filter by name (en/ar) or passport number",
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer list for dropdown",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="object",
     *                     @OA\Property(property="en", type="string", example="John Smith"),
     *                     @OA\Property(property="ar", type="string", example="جون سميث")
     *                 ),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="passport_number", type="string", example="A1234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function list(Request $request)
    {
        if (!$request->user()->can('view customer')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $query = CustomerInfo::query()
            ->select('id', 'name_en', 'name_ar', 'email', 'passport_number');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('passport_number', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('name_en')->get(), Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/customers/{id}",
     *     summary="Get a single customer with full booking details",
     *     description="Returns the customer record along with every booking they are linked to. Each booking includes its unit, building, payment plan, installments, booking-level approvals, SPA (with approvals), and reservation form (with approvals). The `pivot.requires_signature` flag indicates whether this customer must sign documents for that specific booking.",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=101),
     *             @OA\Property(property="name_en", type="string", example="John Smith"),
     *             @OA\Property(property="name_ar", type="string", example="جون سميث"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="passport_number", type="string", example="N001234567"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1992-02-05"),
     *             @OA\Property(property="gender", type="string", example="Male"),
     *             @OA\Property(property="nationality", type="string", example="Syrian Arab Republic"),
     *             @OA\Property(
     *                 property="bookings",
     *                 type="array",
     *                 description="All bookings this customer is linked to",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=42),
     *                     @OA\Property(property="status", type="string", example="Pre-Booked"),
     *                     @OA\Property(property="discount", type="number", format="float", example=5),
     *                     @OA\Property(
     *                         property="pivot",
     *                         type="object",
     *                         @OA\Property(property="requires_signature", type="boolean", example=true, description="Whether this customer must sign documents for this booking")
     *                     ),
     *                     @OA\Property(
     *                         property="unit",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="unit_no", type="string", example="A-203"),
     *                         @OA\Property(
     *                             property="building",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=3),
     *                             @OA\Property(property="name", type="string", example="Sunrise Tower"),
     *                             @OA\Property(property="location", type="string", example="Downtown")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="payment_plan",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Standard Plan")
     *                     ),
     *                     @OA\Property(
     *                         property="installments",
     *                         type="array",
     *                         description="Payment installments for this booking",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="amount", type="number", format="float", example=50000),
     *                             @OA\Property(property="due_date", type="string", format="date", example="2026-09-01"),
     *                             @OA\Property(property="status", type="string", example="Pending")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="approvals",
     *                         type="array",
     *                         description="Booking-level approvals",
     *                         @OA\Items(ref="#/components/schemas/Approval")
     *                     ),
     *                     @OA\Property(
     *                         property="spa",
     *                         type="object",
     *                         nullable=true,
     *                         description="Sales and Purchase Agreement",
     *                         @OA\Property(property="id", type="integer", example=7),
     *                         @OA\Property(property="status", type="string", example="Pending"),
     *                         @OA\Property(
     *                             property="approvals",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Approval")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="reservation_form",
     *                         type="object",
     *                         nullable=true,
     *                         description="Reservation form associated with the booking",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="status", type="string", example="Signed"),
     *                         @OA\Property(
     *                             property="approvals",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Approval")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
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
            'bookings.unit.building',
            'bookings.paymentPlan',
            'bookings.installments',
            'bookings.approvals',
            'bookings.spa.approvals',
            'bookings.reservationForm.approvals'
        ])->find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($customer, Response::HTTP_OK);
    }

}


