<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @OA\Schema (
 *     schema="Approval",
 *     type="object",
 *     title="Approval",
 *     required={"id","ref_id","ref_type","approved_by","approval_type","status","created_at"},
 *     @OA\Property(property="id",             type="integer", format="int64",    example=1),
 *     @OA\Property(property="ref_id",         type="integer", format="int64",    example=42),
 *     @OA\Property(property="ref_type", type="string", example="App\\Models\\Booking", description="Polymorphic model class name"),
 *     @OA\Property(property="approved_by",    type="integer", format="int64",    example=17),
 *     @OA\Property(property="approval_type",  type="string",  example="Sales"),
 *     @OA\Property(property="status", type="string", example="Approved", description="One of: Pending, Approved, Rejected"),
 *     @OA\Property(property="created_at",     type="string",  format="date-time", example="2025-05-02T15:58:33Z")
 * )
 * @property int $id
 * @property int $ref_id
 * @property string $ref_type Unit, Booking
 * @property int $approved_by
 * @property string $approval_type CSO, Accountant, CFO, CEO
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $approvalable
 * @property-read \App\Models\User|null $approvedByUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereRefId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereRefType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval withoutTrashed()
 */
	class Approval extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $unit_id
 * @property string $status
 * @property numeric $discount
 * @property numeric $price
 * @property string|null $receipt_path
 * @property int|null $agent_id
 * @property int|null $sale_source_id
 * @property string|null $agency_com_agent
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $booked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $payment_plan_id
 * @property numeric|null $eoi_amount
 * @property int|null $created_by
 * @property-read \App\Models\User|null $agent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\BrokerCommission|null $brokerCommission
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerInfo> $customerInfos
 * @property-read int|null $customer_infos_count
 * @property-read \App\Models\DldDocument|null $dldDocument
 * @property-read mixed $latest_approved_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InstallmentPayment> $installmentPayments
 * @property-read int|null $installment_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Installment> $installments
 * @property-read int|null $installments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \App\Models\PaymentPlan|null $paymentPlan
 * @property-read \App\Models\ReservationForm|null $reservationForm
 * @property-read \App\Models\User|null $saleSource
 * @property-read \App\Models\ReservationForm|null $signedReservationForm
 * @property-read \App\Models\SPA|null $signedSpa
 * @property-read \App\Models\SPA|null $spa
 * @property-read \App\Models\Unit|null $unit
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereAgencyComAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereBookedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereEoiAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking wherePaymentPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereReceiptPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereSaleSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking withoutTrashed()
 */
	class Booking extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property int $broker_user_id
 * @property int $commission_rate_id
 * @property numeric $commission_percentage
 * @property numeric $base_amount
 * @property numeric $commission_amount
 * @property numeric $paid_amount
 * @property numeric $remaining_amount
 * @property \App\Enums\BrokerCommissionStatusEnum $status
 * @property \Illuminate\Support\Carbon $eligible_at
 * @property \Illuminate\Support\Carbon $calculated_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\User|null $broker
 * @property-read \App\Models\User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommissionPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\BrokerCommissionRate $rate
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereBaseAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereBrokerUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereCalculatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereCommissionAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereCommissionPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereCommissionRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereEligibleAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereRemainingAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommission withoutTrashed()
 */
	class BrokerCommission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $broker_commission_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property numeric $amount
 * @property string|null $payment_method
 * @property string|null $reference_number
 * @property string|null $receipt_path
 * @property string|null $notes
 * @property int|null $paid_by
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\BrokerCommission|null $commission
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $paidBy
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereBrokerCommissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment wherePaidBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereReceiptPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionPayment withoutTrashed()
 */
	class BrokerCommissionPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property numeric $percentage
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommission> $commissions
 * @property-read int|null $commissions_count
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate effectiveAt(\Carbon\CarbonInterface $at)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereEffectiveTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerCommissionRate whereUpdatedBy($value)
 */
	class BrokerCommissionRate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $license_number
 * @property string|null $rera_registration_number
 * @property string|null $address
 * @property string|null $po_box
 * @property string|null $telephone
 * @property string|null $representative
 * @property string|null $designation
 * @property string|null $stamp_path
 * @property \Illuminate\Support\Carbon|null $agreed_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereAgreedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereDesignation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile wherePoBox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereRepresentative($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereReraRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereStampPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereTelephone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerProfile withoutTrashed()
 */
	class BrokerProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $location
 * @property string $status
 * @property string $ecd
 * @property string|null $image_path
 * @property string $plot_no
 * @property string|null $project_no
 * @property int $added_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Unit> $units
 * @property-read int|null $units_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereAddedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereEcd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building wherePlotNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereProjectNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building withoutTrashed()
 */
	class Building extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_info_id
 * @property string $doc_type
 * @property string $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\CustomerInfo|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereCustomerInfoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereDocType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerDoc withoutTrashed()
 */
	class CustomerDoc extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name_en
 * @property string|null $name_ar
 * @property string $passport_number
 * @property string|null $emirates_id_number
 * @property \Illuminate\Support\Carbon $birth_date
 * @property string $gender
 * @property string $nationality_en
 * @property string|null $nationality_ar
 * @property \Illuminate\Support\Carbon|null $issuance_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string $email
 * @property string $phone_number
 * @property string $address_en
 * @property string|null $address_ar
 * @property int $requires_signature
 * @property int|null $booking_id
 * @property string|null $document_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerDoc> $docs
 * @property-read int|null $docs_count
 * @property array $address
 * @property array $name
 * @property array $nationality
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereAddressAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereAddressEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereEmiratesIdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereIssuanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereNameAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereNationalityAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereNationalityEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo wherePassportNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereRequiresSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerInfo withoutTrashed()
 */
	class CustomerInfo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $device_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceToken whereUserId($value)
 */
	class DeviceToken extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property string $file_path
 * @property int $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DldDocument whereUploadedBy($value)
 */
	class DldDocument extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed|null $typed_value
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralSetting whereValue($value)
 */
	class GeneralSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $unit_id
 * @property int $created_by
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Unit|null $unit
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holding withoutTrashed()
 */
	class Holding extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payment_plan_id
 * @property int|null $booking_id
 * @property string $description
 * @property numeric $percentage
 * @property \Illuminate\Support\Carbon $date
 * @property numeric $amount
 * @property \App\Enums\InstallmentStatusEnum $status
 * @property numeric $paid_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\PaymentPlan|null $paymentPlan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InstallmentPayment> $payments
 * @property-read int|null $payments_count
 * @property-read mixed $remaining_amount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InstallmentPayment> $verifiedPayments
 * @property-read int|null $verified_payments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment unpaid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment wherePaymentPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installment withoutTrashed()
 */
	class Installment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $installment_id
 * @property int $booking_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property numeric $amount
 * @property string|null $payment_method
 * @property string|null $reference_number
 * @property string|null $receipt_path
 * @property \App\Enums\InstallmentPaymentStatusEnum $status
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $notes
 * @property string|null $rejection_reason
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Installment|null $installment
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\User|null $verifiedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment pendingVerification()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereInstallmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereReceiptPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment whereVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstallmentPayment withoutTrashed()
 */
	class InstallmentPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $invoice_number
 * @property int $booking_id
 * @property int|null $installment_payment_id
 * @property \App\Enums\InvoiceTypeEnum $type
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property numeric $subtotal
 * @property numeric $vat_rate
 * @property numeric $vat_amount
 * @property numeric $total
 * @property \App\Enums\InvoiceStatusEnum $status
 * @property string|null $pdf_path
 * @property int $issued_by
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\InstallmentPayment|null $installmentPayment
 * @property-read \App\Models\User|null $issuedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice issued()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInstallmentPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIssuedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVatAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVatRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutTrashed()
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $token
 * @property string $user_type Broker, Contractor
 * @property \Illuminate\Support\Carbon|null $expired_at
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink whereUserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OneTimeLink withoutTrashed()
 */
	class OneTimeLink extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property numeric $dld_fee_percentage
 * @property numeric $admin_fee
 * @property numeric $EOI
 * @property numeric $booking_percentage
 * @property numeric $handover_percentage
 * @property bool $post_handover_enabled
 * @property int $post_handover_months
 * @property bool $is_default
 * @property array<array-key, mixed> $blocks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Installment> $installments
 * @property-read int|null $installments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereAdminFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereBlocks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereBookingPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereDldFeePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereEOI($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereHandoverPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan wherePostHandoverEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan wherePostHandoverMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentPlan withoutTrashed()
 */
	class PaymentPlan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property string $file_path
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $company_signed_at
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string|null $signed_file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Booking|null $booking
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereCompanySignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereSignedFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationForm withoutTrashed()
 */
	class ReservationForm extends \Eloquent implements \App\Contracts\Documents\SignableDocument {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property string $file_path
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $company_signed_at
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string|null $signed_file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Booking|null $booking
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereCompanySignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereSignedFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPA whereUpdatedAt($value)
 */
	class SPA extends \Eloquent implements \App\Contracts\Documents\SignableDocument {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $unit_id
 * @property int $generated_by_id
 * @property string $offer_date
 * @property numeric $offer_price
 * @property numeric $discount
 * @property numeric|null $eoi_amount
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\User|null $generatedBy
 * @property-read \App\Models\Unit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereEoiAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereGeneratedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereOfferDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereOfferPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOffer whereUpdatedAt($value)
 */
	class SalesOffer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $signable_type
 * @property int $signable_id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property string $recipient_email
 * @property string|null $recipient_name
 * @property \App\Enums\DocumentType $document_type
 * @property string $token_hash
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string|null $client_ip
 * @property string|null $user_agent
 * @property string|null $signature_image_path
 * @property string|null $signature_source
 * @property \Illuminate\Support\Carbon|null $withdrawn_at
 * @property int|null $withdrawn_by
 * @property string|null $withdraw_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $documentable
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $signable
 * @property-read \App\Models\User|null $withdrawnBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink forDocumentType(\App\Enums\DocumentType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink forRecipient(string $email)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink signed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereClientIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereDocumentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereDocumentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereRecipientEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereRecipientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereSignableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereSignableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereSignatureImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereSignatureSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereTokenHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereWithdrawReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereWithdrawnAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SigningLink whereWithdrawnBy($value)
 */
	class SigningLink extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $prop_type
 * @property string $unit_type
 * @property string $unit_no
 * @property string $floor
 * @property string $parking
 * @property string|null $amenity
 * @property numeric $internal_square
 * @property numeric $external_square
 * @property int $furnished
 * @property string $unit_view
 * @property numeric $price
 * @property numeric $min_price
 * @property numeric $pre_lunch_price
 * @property numeric $lunch_price
 * @property int $building_id
 * @property string $status
 * @property string|null $floor_plan
 * @property \Illuminate\Support\Carbon|null $status_changed_at
 * @property int|null $contractor_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Building|null $building
 * @property-read \App\Models\User|null $contractor
 * @property-read float $external_square_m
 * @property-read float $internal_square_m
 * @property-read float $total_square
 * @property-read float $total_square_m
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Holding> $holdings
 * @property-read int|null $holdings_count
 * @property-read \App\Models\Booking|null $latestBooking
 * @property-read \App\Models\Holding|null $latestHolding
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesOffer> $salesOffers
 * @property-read int|null $sales_offers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UnitUpdate> $unitUpdates
 * @property-read int|null $unit_updates_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereAmenity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereContractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereExternalSquare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereFloor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereFloorPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereFurnished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereInternalSquare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereLunchPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereMinPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereParking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit wherePreLunchPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit wherePropType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereStatusChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereUnitNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereUnitType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereUnitView($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit withoutTrashed()
 */
	class Unit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $unit_id
 * @property string $description
 * @property string|null $attachment_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Unit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnitUpdate withoutTrashed()
 */
	class UnitUpdate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookingsAsAgent
 * @property-read int|null $bookings_as_agent_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookingsAsSaleSource
 * @property-read int|null $bookings_as_sale_source_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommission> $brokerCommissions
 * @property-read int|null $broker_commissions_count
 * @property-read \App\Models\BrokerProfile|null $brokerProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Unit> $contractorUnits
 * @property-read int|null $contractor_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommissionPayment> $createdBrokerCommissionPayments
 * @property-read int|null $created_broker_commission_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommissionRate> $createdBrokerCommissionRates
 * @property-read int|null $created_broker_commission_rates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommission> $createdBrokerCommissions
 * @property-read int|null $created_broker_commissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DeviceToken> $deviceTokens
 * @property-read int|null $device_tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DldDocument> $dldDocuments
 * @property-read int|null $dld_documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserDoc> $docs
 * @property-read int|null $docs_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\OneTimeLink|null $oneTimeLink
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommissionPayment> $recordedBrokerCommissionPayments
 * @property-read int|null $recorded_broker_commission_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\UserSignature|null $signature
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommissionPayment> $updatedBrokerCommissionPayments
 * @property-read int|null $updated_broker_commission_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommissionRate> $updatedBrokerCommissionRates
 * @property-read int|null $updated_broker_commission_rates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BrokerCommission> $updatedBrokerCommissions
 * @property-read int|null $updated_broker_commissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SigningLink> $withdrawnSigningLinks
 * @property-read int|null $withdrawn_signing_links_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $doc_type
 * @property string $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereDocType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDoc withoutTrashed()
 */
	class UserDoc extends \Eloquent implements \App\Contracts\Documents\SignableDocument {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $image_path
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSignature withoutTrashed()
 */
	class UserSignature extends \Eloquent {}
}

