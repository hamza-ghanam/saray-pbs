<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneralSettingRequest;
use App\Http\Requests\UpdateGeneralSettingRequest;
use App\Http\Resources\GeneralSettingResource;
use App\Models\GeneralSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\Response;

class GeneralSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage general settings'),
        ];
    }

    public function index(Request $request)
    {
        $query = GeneralSetting::query();

        if ($request->filled('group')) {
            $query->where('group', $request->string('group'));
        }

        if ($request->filled('key')) {
            $query->where('key', 'like', '%' . $request->string('key') . '%');
        }

        $settings = $query->orderBy('group')->orderBy('key')->paginate(20);

        return GeneralSettingResource::collection($settings);
    }

    public function show(GeneralSetting $general_setting): GeneralSettingResource
    {
        return new GeneralSettingResource($general_setting);
    }

    public function store(StoreGeneralSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (($data['type'] ?? 'string') === 'json' && isset($data['value']) && is_array($data['value'])) {
            $data['value'] = json_encode($data['value'], JSON_UNESCAPED_UNICODE);
        }

        if (($data['type'] ?? 'string') !== 'json' && isset($data['value']) && is_bool($data['value'])) {
            $data['value'] = $data['value'] ? '1' : '0';
        }

        $setting = GeneralSetting::create($data);

        return response()->json([
            'message' => 'General setting created successfully.',
            'data' => new GeneralSettingResource($setting),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateGeneralSettingRequest $request, GeneralSetting $general_setting): JsonResponse
    {
        $data = $request->validated();

        $type = $data['type'] ?? $general_setting->type;

        if ($type === 'json' && array_key_exists('value', $data) && is_array($data['value'])) {
            $data['value'] = json_encode($data['value'], JSON_UNESCAPED_UNICODE);
        }

        if ($type !== 'json' && array_key_exists('value', $data) && is_bool($data['value'])) {
            $data['value'] = $data['value'] ? '1' : '0';
        }

        $general_setting->update($data);

        return response()->json([
            'message' => 'General setting updated successfully.',
            'data' => new GeneralSettingResource($general_setting->fresh()),
        ]);
    }

    public function destroy(GeneralSetting $general_setting): JsonResponse
    {
        $general_setting->delete();

        return response()->json([
            'message' => 'General setting deleted successfully.',
        ]);
    }
}
