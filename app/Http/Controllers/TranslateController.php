<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/translate/arabic",
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
    public function translateToArabic(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:255',
        ]);

        $text = $request->input('text');

        $tr = new GoogleTranslate('ar');
        $tr->setSource('en');

        $translated = $tr->translate($text);

        return response()->json([
            'translated' => $translated,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/translate/arabic/many",
     *     summary="Translate multiple English texts into Arabic",
     *     description="Accepts an array of English strings and returns their Arabic translations.",
     *     operationId="translateManyToArabic",
     *     tags={"Translation"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"texts"},
     *             @OA\Property(
     *                 property="texts",
     *                 type="array",
     *                 @OA\Items(type="string", maxLength=255),
     *                 example={
     *                     "name": "Hamza",
     *                     "nationality": "Syrian",
     *                     "address": "elsewhere"
     *                }
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
    public function translateManyToArabic(Request $request)
    {
        $data = $request->validate([
            'texts'   => 'required|array|min:1',
            'texts.*' => 'required|string|max:255',
        ]);

        $target = $request->input('target', 'ar');
        $source = $request->input('source', 'en');

        $tr = new GoogleTranslate($target);
        $tr->setSource($source);

        $translations = [];

        foreach ($data['texts'] as $key => $text) {
            $translations[$key] = $tr->translate($text);
        }

        return response()->json([
            'translations' => $translations,
        ]);
    }
}
