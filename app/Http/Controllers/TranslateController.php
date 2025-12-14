<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\Request;

class TranslateController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/translate/ar",
     *     summary="Translate a single English text into Arabic",
     *     description="Takes a single English string and returns the Arabic translation.",
     *     operationId="translateToArabic",
     *     tags={"Translation"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", maxLength=255, example="John Smith")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful translation",
     *         @OA\JsonContent(
     *             @OA\Property(property="translated", type="string", example="جون سميث")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function translateToArabic(Request $request, TranslationService $service)
    {
        $request->validate([
            'text' => 'required|string|max:255',
        ]);

        return response()->json([
            'translated' => $service->translate($request->input('text')),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/translate/ar/multiple",
     *     summary="Translate multiple English texts into Arabic",
     *     description="Accepts an object of key => English text and returns an object with the same keys translated to Arabic.",
     *     operationId="translateManyToArabic",
     *     tags={"Translation"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"texts"},
     *             @OA\Property(
     *                 property="texts",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                      type="string",
     *                      maxLength=255
     *                 ),
     *                 example={
     *                     "name": "Hamza",
     *                     "nationality": "Syrian",
     *                     "address": "elsewhere"
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful translation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="translations",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                      type="string",
     *                 ),
     *                 example={
     *                     "name": "حمزة",
     *                     "nationality": "سوري",
     *                     "address": "في مكان آخر"
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function translateMultipleToArabic(Request $request, TranslationService $service)
    {
        $data = $request->validate([
            'texts'   => 'required|array|min:1',
            'texts.*' => 'required|string|max:255',
        ]);

        return response()->json([
            'translations' => $service->translateMultiple($data['texts']),
        ]);
    }
}
