<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Generate a unique sequential invoice number.
     * Uses DB lock to prevent duplicate numbers under concurrent requests.
     * Format: INV-{YEAR}-{5-digit sequence} e.g. INV-2026-00001
     */
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $key  = "invoice_counter_{$year}";

            // Lock the row to prevent race conditions
            $setting = DB::table('general_settings')
                ->where('group', 'invoicing')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($setting) {
                $next = (int) $setting->value + 1;
                DB::table('general_settings')
                    ->where('group', 'invoicing')
                    ->where('key', $key)
                    ->update(['value' => $next]);
            } else {
                $next = 1;
                DB::table('general_settings')->insert([
                    'group'       => 'invoicing',
                    'key'         => $key,
                    'value'       => $next,
                    'type'        => 'integer',
                    'description' => "Invoice counter for year {$year}",
                    'is_public'   => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            return sprintf('INV-%d-%05d', $year, $next);
        });
    }
}
