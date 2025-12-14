<?php

namespace App\Mappers;

class BilingualFieldsMapper
{
    /**
     * Merge flat EN + AR into one bilingual structure under the same key:
     * first_name + first_name_ar  =>  first_name: {en:..., ar:...}
     */
    public static function map(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $enKey = $field;
            $arKey = "{$field}_ar";

            $hasEn = array_key_exists($enKey, $data);
            $hasAr = array_key_exists($arKey, $data);

            if (!$hasEn && !$hasAr) {
                continue;
            }

            // 1) snapshot values FIRST
            $enVal = $data[$enKey] ?? null;
            $arVal = $data[$arKey] ?? null;

            // 2) remove old keys
            unset($data[$enKey], $data[$arKey]);

            // 3) set the new nested structure
            $data[$field] = [
                'en' => $enVal,
                'ar' => $arVal,
            ];
        }

        return $data;
    }
}
