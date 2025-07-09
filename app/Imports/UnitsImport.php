<?php

namespace App\Imports;

use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Carbon\Carbon;

class UnitsImport implements ToModel, WithHeadingRow, WithCalculatedFormulas, WithBatchInserts, WithChunkReading
{
    use Importable;

    protected $building;
    public static int $totalCount = 0;

    public function __construct($building)
    {
        $this->building = $building;
    }


    /** Row 3 in Excel (the “NUMBER, FLOOR, USE, …” row) */
    public function headingRow(): int { return 3; }

    /** Row 4 in Excel (the first data row) */
    public function startRow(): int { return 4; }

    protected int $currentRow = 3;
    public array $skippedRows = [];

    /**
     * Map each row to a Unit model.
     */
    public function model(array $row)
    {
        $this->currentRow++;
        // dd(array_keys($row));

        try {
            if (! is_numeric($row['unit_number']) || strtolower($row['unit_number']) === 'total') {
                Log::info('Skipped: invalid or summary row.');
                return null;  // skip this row entirely
            }

            $exists = Unit::where('building_id', $this->building->id)
                ->where('unit_no', $row['unit_number'])
                ->exists();

            if ($exists) {
                $this->skippedRows[] = "Skipped: duplicate unit_no {$row['unit_number']} in building {$this->building->id}";
                return null;
            }

            self::$totalCount++;

            return new Unit([
                'prop_type'         => 'Residential',
                'unit_type'         => $row['type'],
                'unit_no'           => $row['unit_number'],
                'floor'             => $row['floor'],
                'parking'           => 1,
                'amenity'      => $row['amenity'],
                'internal_square'   => $row['internal'],
                'external_square'   => $row['external'],
                'furnished'         => false,
                'unit_view'         => $row['view'],
                'price'             => $row['list_price'],
                'min_price'         => $row['min_price'] ?? null,
                'pre_lunch_price'   => $row['pre_lunch_price'] ?? null,
                'lunch_price'       => $row['lunch_price'] ?? null,
                'building_id'       => $this->building->id,
                'status'            => 'Pending',
                'status_changed_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Error on row {$this->currentRow}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Insert in batches of 500 for speed.
     */
    public function batchSize(): int { return 500; }

    public function chunkSize(): int { return 500; }
}
