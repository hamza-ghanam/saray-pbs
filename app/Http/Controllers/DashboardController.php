<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardOverviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/dashboard/overview",
     *   tags={"Dashboard"},
     *   security={{"sanctum":{}}},
     *   summary="Dashboard overview (role-aware)",
     *   @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date"), example="2026-03-01"),
     *   @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date"), example="2026-03-03"),
     *   @OA\Parameter(name="project_id", in="query", @OA\Schema(type="integer"), example=1),
     *   @OA\Parameter(name="debug", in="query", @OA\Schema(type="integer", enum={0,1}), example=0),
     *   @OA\Response(
     *     response=200,
     *     description="OK"
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function overview(Request $request)
    {
        $validated = $request->validate([
            'from'       => ['nullable', 'date_format:Y-m-d'],
            'to'         => ['nullable', 'date_format:Y-m-d'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'debug'      => ['nullable', 'in:0,1'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $projectId = $validated['project_id'] ?? null;

        // Guard: from/to must be in correct order and not exceed 90 days.
        if ($from && $to) {
            $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
            $toDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

            if ($fromDate->gt($toDate)) {
                return response()->json([
                    'error' => 'Invalid date range: from must be before or equal to to.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            /*
            if ($fromDate->diffInDays($toDate) > 90) {
                return response()->json([
                    'error' => 'Invalid date range: maximum allowed range is 90 days.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            */
        }

        $service = new DashboardOverviewService(
            from: $from,
            to: $to,
            projectId: $projectId ? (int) $projectId : null
        );

        $data = $service->handle();

        // Optional debug payload (only for authorised users)
        if (($validated['debug'] ?? '0') === '1' && $request->user()?->can('dashboard.debug')) {
            $data['debug'] = [
                'requested' => [
                    'from' => $from,
                    'to' => $to,
                    'project_id' => $projectId,
                ],
            ];
        }

        return response()->json(['data' => $data]);
    }
}
