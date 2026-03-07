<?php

namespace App\Services\Dashboard;

use App\Models\Booking;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardOverviewService
{
    private Carbon $from;
    private Carbon $to;
    private ?int $projectId;
    private $user;
    private string $role;

    public function __construct(?string $from = null, ?string $to = null, ?int $projectId = null)
    {
        $this->user = Auth::user();
        $this->role = $this->user?->getRoleNames()->first() ?? 'unknown';

        $this->from = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $this->to = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $this->projectId = $projectId ?: null;
    }

    public function handle(): array
    {
        // Cache per user + range + project (60s)
        return Cache::remember($this->cacheKey(), now()->addSeconds(60), function () {
            $meta = $this->meta();

            // Aggregated (efficient) queries
            $bookingAgg = $this->bookingAggregates();
            $unitAgg = $this->unitAggregates();

            $payload = [
                'meta' => $meta,
                'kpis' => $this->kpis($bookingAgg, $unitAgg),
                'breakdowns' => $this->breakdowns($bookingAgg, $unitAgg),
            ];

            // Always include these keys, initialize empty
            $payload['queues'] = [
                'approvals' => [
                    'bookings' => [],
                ],
            ];
            $payload['alerts'] = [];
            $payload['tables'] = [
                'latest_bookings' => [],
                'units_needing_attention' => [],
            ];

            // Fill only if allowed by role
            if ($this->canSeeApprovals()) {
                $payload['queues']['approvals']['bookings'] = $this->bookingApprovalQueue();
            }
            if ($this->canSeeAlerts()) {
                $payload['alerts'] = $this->alerts();
            }
            if ($this->canSeeTables()) {
                $payload['tables'] = [
                    'latest_bookings' => $this->latestBookings(),
                    'units_needing_attention' => $this->unitsNeedingAttention(),
                ];
            }

            return $payload;
        });
    }

    /* ===============================
        META
    =============================== */

    private function meta(): array
    {
        return [
            'range' => [
                'from' => $this->from->toDateString(),
                'to' => $this->to->toDateString(),
            ],
            'timezone' => config('app.timezone'),
            'role' => $this->role,
            'project_id' => $this->projectId,
        ];
    }

    private function cacheKey(): string
    {
        // Include permissions fingerprint to avoid cross-role/permission cache leakage
        $permHash = md5(implode('|', $this->user->getAllPermissions()->pluck('name')->sort()->values()->all()));

        return implode(':', [
            'dash_overview',
            'u' . $this->user->id,
            'r' . $this->role,
            'p' . $permHash,
            'from' . $this->from->format('Ymd'),
            'to' . $this->to->format('Ymd'),
            'proj' . ($this->projectId ?? 'all'),
        ]);
    }

    /* ===============================
        ROLE-AWARE VISIBILITY
    =============================== */

    private function canSeeApprovals(): bool
    {
        return in_array($this->role, ['CEO', 'CSO', 'COO', 'CRM Officer', 'System Maintenance'], true);
    }

    private function canSeeTables(): bool
    {
        return in_array($this->role, ['CEO', 'CSO', 'COO', 'CRM Officer', 'System Maintenance'], true);
    }

    private function canSeeAlerts(): bool
    {
        return in_array($this->role, ['CEO', 'CSO', 'COO', 'CRM Officer', 'System Maintenance'], true);
    }

    /* ===============================
        AGGREGATES (FAST)
    =============================== */

    private function bookingAggregates(): array
    {
        // One aggregated query for: counts by key statuses + sum of value for (Booked+Completed) + cancelled value
        $q = Booking::query()
            ->when($this->projectId, function ($q) {
                $q->whereHas('unit', function ($uq) {
                    $uq->where('building_id', $this->projectId);
                });
            })
            ->whereBetween('updated_at', [$this->from, $this->to]);

        $row = $q->selectRaw(
            "
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_pre_booked,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_rf_pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_spa_pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_booked,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_completed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_cancelled,
            SUM(CASE WHEN status = ? THEN COALESCE(price,0) ELSE 0 END) AS sum_value_booked,
            SUM(CASE WHEN status = ? THEN COALESCE(price,0) ELSE 0 END) AS sum_value_cancelled,
            SUM(CASE WHEN status IN (?, ?) THEN COALESCE(price,0) ELSE 0 END) AS sum_value_booked_completed
            ",
            [
                Booking::STATUS_PRE_BOOKED,
                Booking::STATUS_RF_PENDING,
                Booking::STATUS_SPA_PENDING,
                Booking::STATUS_BOOKED,
                Booking::STATUS_COMPLETED,
                Booking::STATUS_CANCELLED,
                Booking::STATUS_BOOKED,
                Booking::STATUS_CANCELLED,
                Booking::STATUS_BOOKED,
                Booking::STATUS_COMPLETED,
            ]
        )->first();

        return [
            'counts' => [
                Booking::STATUS_PRE_BOOKED => (int)($row->cnt_pre_booked ?? 0),
                Booking::STATUS_RF_PENDING => (int)($row->cnt_rf_pending ?? 0),
                Booking::STATUS_SPA_PENDING => (int)($row->cnt_spa_pending ?? 0),
                Booking::STATUS_BOOKED => (int)($row->cnt_booked ?? 0),
                Booking::STATUS_COMPLETED => (int)($row->cnt_completed ?? 0),
                Booking::STATUS_CANCELLED => (int)($row->cnt_cancelled ?? 0),
            ],
            'sum_value_booked_completed' => (float)($row->sum_value_booked_completed ?? 0),
            'sum_value_booked' => (float)($row->sum_value_booked ?? 0),
            'sum_value_cancelled' => (float)($row->sum_value_cancelled ?? 0),
        ];
    }

    private function unitAggregates(): array
    {
        // Grouped counts by status (single query)
        $rows = Unit::query()
            ->when($this->projectId, fn($q) => $q->where('building_id', $this->projectId))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->status] = (int)$r->count;
        }

        return [
            'counts_by_status' => $map,
            'available_units' => (int)($map[Unit::STATUS_AVAILABLE] ?? 0),
        ];
    }

    /* ===============================
        KPIs + BREAKDOWNS
    =============================== */

    private function kpis(array $bookingAgg, array $unitAgg): array
    {
        $booked = $bookingAgg['counts'][Booking::STATUS_BOOKED] ?? 0;
        $completed = $bookingAgg['counts'][Booking::STATUS_COMPLETED] ?? 0;
        $cancelled = $bookingAgg['counts'][Booking::STATUS_CANCELLED] ?? 0;
        $sum_value_booked_completed = $bookingAgg['sum_value_booked_completed'] ?? 0;
        $sum_value_booked = $bookingAgg['sum_value_booked'] ?? 0;
        $sum_value_cancelled = $bookingAgg['sum_value_cancelled'] ?? 0;
        $available_units = (int)($unitAgg['available_units'] ?? 0);
        $counts_by_status = $unitAgg['counts_by_status'] ?? [];
        $booked_units = (int)($counts_by_status[Unit::STATUS_BOOKED] ?? 0);
        $den = ($booked + $completed + $cancelled);
        $booked_completed_count = ($booked + $completed);
        $absorption_den = (int)($counts_by_status[Unit::STATUS_AVAILABLE] ?? 0);

        return [
            'available_units' => [
                'value' => $available_units,
            ],
            'total_booking_value_mtd' => [
                'value' => (float)$sum_value_booked_completed,
            ],
            'total_booked_units_value_mtd' => [
                'value' => (float)$sum_value_booked,
            ],
            'cancellation_rate_mtd' => [
                'value' => $den > 0 ? round($cancelled / $den, 4) : 0.0,
            ],
            // --- New KPIs ---
            'net_booking_value_mtd' => [
                'value' => round($sum_value_booked_completed - $sum_value_cancelled, 2),
            ],
            'average_unit_price_mtd' => [
                'value' => $booked_completed_count > 0 ? round($sum_value_booked_completed / $booked_completed_count, 2) : 0.0,
            ],
            'inventory_absorption_rate' => [
                'value' => $absorption_den > 0 ? round($booked_units / $absorption_den, 4) : 0.0,
            ],
            'units_stuck_in_hold' => [
                'value' => $this->unitsStuckInHoldCount(7),
            ],
        ];
    }

    private function breakdowns(array $bookingAgg, array $unitAgg): array
    {
        $unitsByStatus = [];
        foreach ($unitAgg['counts_by_status'] as $status => $count) {
            $unitsByStatus[] = ['status' => $status, 'count' => $count];
        }

        $bookingsByStatus = [];
        foreach ($bookingAgg['counts'] as $status => $count) {
            $bookingsByStatus[] = ['status' => $status, 'count' => $count];
        }

        return [
            'units_by_status' => $unitsByStatus,
            'bookings_by_status' => $bookingsByStatus,
            'top_projects_by_booking_value_mtd' => $this->topProjectsByBookingValueMtd(5),
        ];
    }

    /* ===============================
        QUEUES (APPROVALS)
    =============================== */

    private function bookingApprovalQueue(): array
    {
        // Role-aware approvals queue
        // - CEO: missing CEO approval
        // - CSO: missing CSO approval
        // - COO/CRM: show pipeline list (no extra filter)
        $q = Booking::query()
            ->when($this->projectId, function ($q) {
                $q->whereHas('unit', fn($uq) => $uq->where('building_id', $this->projectId));
            })
            ->whereIn('status', [
                Booking::STATUS_PRE_BOOKED,
                Booking::STATUS_RF_PENDING,
                Booking::STATUS_SPA_PENDING,
            ])
            ->latest('id')
            ->limit(15);

        if ($this->role === 'CEO') {
            $q->whereDoesntHave('approvals', fn($aq) => $aq->whereIn('approval_type', ['CEO', 'System Maintenance'])
                ->where('status', 'Approved'));
        } elseif ($this->role === 'CSO') {
            $q->whereDoesntHave('approvals', fn($aq) => $aq->whereIn('approval_type', ['CSO'])
                ->where('status', 'Approved'));
        }

        return $q->select(['id', 'status', 'created_at', 'unit_id'])
            ->addSelect(DB::raw('COALESCE(price,0) as total_value'))
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'status' => $b->status,
                'total_value' => (float)($b->total_value ?? 0),
                'created_at' => $b->created_at?->toIso8601String(),
                'unit_id' => $b->unit_id,
            ])
            ->toArray();
    }

    /* ===============================
        ALERTS
    =============================== */

    private function alerts(): array
    {
        $alerts = [];

        // Stuck bookings > 48h (simple MVP rule)
        $thresholdHours = 48;

        $stuck = Booking::query()
            ->when($this->projectId, function ($q) {
                $q->whereHas('unit', fn($uq) => $uq->where('building_id', $this->projectId));
            })
            ->whereIn('status', [
                Booking::STATUS_PRE_BOOKED,
                Booking::STATUS_RF_PENDING,
                Booking::STATUS_SPA_PENDING,
            ])
            ->where('updated_at', '<', now()->subHours($thresholdHours))
            ->count();

        if ($stuck > 0) {
            $alerts[] = [
                'type' => 'stuck_bookings',
                'count' => $stuck,
                'threshold_hours' => $thresholdHours,
            ];
        }

        return $alerts;
    }

    /* ===============================
        TABLES
    =============================== */

    private function latestBookings(): array
    {
        return Booking::query()
            ->with(['unit:id,unit_no'])
            ->when($this->projectId, function ($q) {
                $q->whereHas('unit', fn($uq) => $uq->where('building_id', $this->projectId));
            })
            ->latest('id')
            ->limit(10)
            ->select(['id', 'unit_id', 'status', 'created_at'])
            ->addSelect(DB::raw('COALESCE(price,0) as total_value'))
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'status' => $b->status,
                'total_value' => (float)($b->total_value ?? 0),
                'created_at' => $b->created_at?->toIso8601String(),
                'unit' => [
                    'id' => $b->unit?->id,
                    'unit_no' => $b->unit?->unit_no,
                ],
            ])
            ->toArray();
    }

    private function unitsNeedingAttention(): array
    {
        return Unit::query()
            ->when($this->projectId, fn($q) => $q->where('building_id', $this->projectId))
            ->whereIn('status', [
                Unit::STATUS_PENDING,
                Unit::STATUS_PRE_HOLD,
                Unit::STATUS_HOLD,
            ])
            ->latest('id')
            ->limit(10)
            ->get(['id', 'unit_no', 'status', 'status_changed_at', 'building_id'])
            ->map(fn($u) => [
                'id' => $u->id,
                'unit_no' => $u->unit_no,
                'status' => $u->status,
                'status_changed_at' => optional($u->status_changed_at)->toIso8601String(),
                'project_id' => $u->building_id,
            ])
            ->toArray();
    }

    /* ===============================
        ADMIN METRICS HELPERS
    =============================== */

    private function unitsStuckInHoldCount(int $days = 7): int
    {
        return Unit::query()
            ->when($this->projectId, fn($q) => $q->where('building_id', $this->projectId))
            ->where('status', Unit::STATUS_HOLD)
            ->whereNotNull('status_changed_at')
            ->where('status_changed_at', '<', now()->subDays($days))
            ->count();
    }

    private function topProjectsByBookingValueMtd(int $limit = 5): array
    {
        $q = Booking::query()
            ->join('units', 'units.id', '=', 'bookings.unit_id')
            ->join('buildings', 'buildings.id', '=', 'units.building_id')
            ->whereBetween('bookings.updated_at', [$this->from, $this->to])
            ->whereIn('bookings.status', [Booking::STATUS_BOOKED, Booking::STATUS_COMPLETED])
            ->when($this->projectId, fn($q) => $q->where('units.building_id', $this->projectId))
            ->groupBy('units.building_id', 'buildings.name')
            ->orderByDesc('total_value')
            ->limit($limit)
            ->selectRaw('units.building_id as project_id, buildings.name as project_name, SUM(COALESCE(bookings.price,0)) as total_value, COUNT(*) as bookings_count');

        return $q->get()->map(fn($r) => [
            'project' => [
                'id' => (int)$r->project_id,
                'name' => $r->project_name,
            ],
            'total_value' => (float)$r->total_value,
            'bookings_count' => (int)$r->bookings_count,
        ])->toArray();
    }
}
