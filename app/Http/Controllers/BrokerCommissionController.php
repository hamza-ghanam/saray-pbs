<?php

namespace App\Http\Controllers;

use App\Actions\BrokerCommission\RecordBrokerCommissionPaymentAction;
use App\Models\BrokerCommission;
use App\Models\BrokerCommissionRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class BrokerCommissionController extends Controller
{
    /////// Broker Commission Rates
    /**
     * @OA\Get(
     *     path="/broker-commissions/rates",
     *     summary="List broker commission rates",
     *     operationId="listBrokerCommissionRates",
     *     tags={"Broker Commissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(response=200, description="Broker commission rates fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listCommissionRates(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('manage broker commission rates')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $rates = BrokerCommissionRate::query()
            ->with(['createdBy:id,name', 'updatedBy:id,name'])
            ->orderByDesc('effective_from')
            ->paginate($perPage);

        return response()->json($rates, Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/broker-commissions/rates/current",
     *     summary="Get current broker commission rate",
     *     operationId="getCurrentBrokerCommissionRate",
     *     tags={"Broker Commissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Current broker commission rate fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="No effective broker commission rate found")
     * )
     */
    public function currentCommissionRate(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('manage broker commission rates')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $rate = BrokerCommissionRate::query()
            ->effectiveAt(now())
            ->with(['createdBy:id,name', 'updatedBy:id,name'])
            ->orderByDesc('effective_from')
            ->first();

        if (!$rate) {
            return response()->json([
                'error' => 'No effective broker commission rate found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($rate, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/broker-commissions/rates",
     *     summary="Create a broker commission rate",
     *     operationId="createBrokerCommissionRate",
     *     tags={"Broker Commissions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"percentage","effective_from"},
     *             @OA\Property(property="percentage", type="number", format="float", example=6.00),
     *             @OA\Property(property="effective_from", type="string", format="date-time", example="2026-03-07 09:00:00")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Broker commission rate created successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error or business rule violation")
     * )
     */
    public function storeCommissionRate(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('manage broker commission rates')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'percentage' => ['required', 'numeric', 'gt:0', 'max:100'],
            'effective_from' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        try {
            $effectiveFrom = Carbon::parse($validated['effective_from']);

            $overlappingRate = BrokerCommissionRate::query()
                ->where('effective_from', '<=', $effectiveFrom)
                ->where(function ($query) use ($effectiveFrom) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $effectiveFrom);
                })
                ->orderByDesc('effective_from')
                ->first();

            $overlappingRate?->update([
                'effective_to' => $effectiveFrom->copy()->subSecond(),
                'updated_by' => $user->id,
            ]);

            $rate = BrokerCommissionRate::query()->create([
                'percentage' => $validated['percentage'],
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rate->load(['createdBy:id,name', 'updatedBy:id,name']);

        return response()->json([
            'message' => 'Broker commission rate created successfully.',
            'data' => $rate,
        ], Response::HTTP_CREATED);
    }

    /////// Broker Commission Rates
    /**
     * @OA\Get(
     *     path="/broker-commissions",
     *     summary="List broker commissions",
     *     operationId="listBrokerCommissions",
     *     tags={"Broker Commissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="broker_user_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", format="int64", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", example="partially_paid")
     *     ),
     *     @OA\Parameter(
     *         name="booking_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", format="int64", example=150)
     *     ),
     *     @OA\Parameter(
     *         name="building_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", format="int64", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-03-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-03-31")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(response=200, description="Broker commissions fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function listCommissions(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('view broker commissions')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'broker_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:due,partially_paid,paid,cancelled'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = BrokerCommission::query()
            ->with([
                'broker:id,name',
                'rate:id,percentage,effective_from,effective_to',
                'booking:id,unit_id,sale_source_id,price,status,booked_at',
                'booking.unit:id,unit_no,building_id',
                'booking.unit.building:id,name',
                'payments:id,broker_commission_id,payment_date,amount,payment_method,reference_number,receipt_path,paid_by,created_at',
                'payments.paidBy:id,name',
            ])
            ->withCount('payments')
            ->orderByDesc('eligible_at')
            ->orderByDesc('id');

        $query->when(isset($validated['broker_user_id']), function (Builder $query) use ($validated) {
            $query->where('broker_user_id', $validated['broker_user_id']);
        });

        $query->when(isset($validated['status']), function (Builder $query) use ($validated) {
            $query->where('status', $validated['status']);
        });

        $query->when(isset($validated['booking_id']), function (Builder $query) use ($validated) {
            $query->where('booking_id', $validated['booking_id']);
        });


        $query->when(isset($validated['date_from']), function (Builder $query) use ($validated) {
            $query->whereDate('eligible_at', '>=', $validated['date_from']);
        });

        $query->when(isset($validated['date_to']), function (Builder $query) use ($validated) {
            $query->whereDate('eligible_at', '<=', $validated['date_to']);
        });

        $commissions = $query->paginate($perPage);

        return response()->json($commissions, Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/broker-commissions/{brokerCommission}",
     *     summary="Show broker commission details",
     *     operationId="showBrokerCommission",
     *     tags={"Broker Commissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brokerCommission",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=200, description="Broker commission fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showCommission(Request $request, BrokerCommission $brokerCommission)
    {
        $user = $request->user();

        if (!$user || !$user->can('view broker commissions')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $brokerCommission->load([
            'broker:id,name,email',
            'rate:id,percentage,effective_from,effective_to',
            'booking:id,unit_id,sale_source_id,price,status,booked_at',
            'booking.unit:id,unit_no,building_id',
            'booking.unit.building:id,name',
            'payments:id,broker_commission_id,payment_date,amount,payment_method,reference_number,receipt_path,paid_by,notes,created_at',
            'payments.paidBy:id,name',
        ]);

        return response()->json($brokerCommission, Response::HTTP_OK);
    }

    /////// Broker Commission Payments
    /**
     * @OA\Post(
     *     path="/broker-commissions/{brokerCommission}/payments",
     *     summary="Record a broker commission payment",
     *     operationId="recordBrokerCommissionPayment",
     *     tags={"Broker Commissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brokerCommission",
     *         in="path",
     *         description="ID of the broker commission",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"amount","payment_date"},
     *                 @OA\Property(property="amount", type="number", format="float", example=20000),
     *                 @OA\Property(property="payment_date", type="string", format="date", example="2026-03-07"),
     *                 @OA\Property(property="payment_method", type="string", nullable=true, example="bank_transfer"),
     *                 @OA\Property(property="reference_number", type="string", nullable=true, example="TRX-123456"),
     *                 @OA\Property(property="receipt", type="string", format="binary", nullable=true, description="Optional payment receipt file upload."),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="First instalment of broker commission."),
     *                 @OA\Property(property="paid_by", type="integer", format="int64", nullable=true, example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Broker commission payment recorded successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or business rule violation"
     *     )
     * )
     */
    public function recordCommissionPayment(
        Request $request,
        BrokerCommission $brokerCommission,
        RecordBrokerCommissionPaymentAction $recordBrokerCommissionPaymentAction
    ) {
        $user = $request->user();

        if (!$user || !$user->can('record broker commission payment')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string'],
            'paid_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptFile = $request->file('receipt');
            $receiptName = 'broker_commission_receipt_' . $brokerCommission->id . '_' . now()->format('Ymd_His') . '.' . $receiptFile->getClientOriginalExtension();
            $receiptPath = $receiptFile->storeAs('brokers/commission/receipts', $receiptName, 'local');
        }

        try {
            $payment = $recordBrokerCommissionPaymentAction->execute(
                brokerCommission: $brokerCommission,
                amount: (float) $validated['amount'],
                paymentDate: $validated['payment_date'],
                paymentMethod: $validated['payment_method'] ?? null,
                referenceNumber: $validated['reference_number'] ?? null,
                receiptPath: $receiptPath,
                notes: $validated['notes'] ?? null,
                paidBy: $validated['paid_by'] ?? null,
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('Record broker commission payment failed', [
                'broker_commission_id' => $brokerCommission->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $payment->load([
            'commission',
            'commission.booking',
            'commission.broker',
            'commission.rate',
            'paidBy',
        ]);

        $brokerCommission = $brokerCommission->fresh()->load([
            'booking',
            'broker',
            'rate',
            'payments',
        ]);

        return response()->json([
            'message' => 'Broker commission payment recorded successfully.',
            'payment' => $payment,
            'broker_commission' => $brokerCommission,
        ], Response::HTTP_CREATED);
    }
}
