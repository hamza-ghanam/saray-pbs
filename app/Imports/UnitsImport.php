<?php

namespace App\Imports;

use App\Models\Unit;
use Illuminate\Support\Collection;
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

    /**
     * Map each row to a Unit model.
     */
    public function model(array $row)
    {
        if (! is_numeric($row['number']) || strtolower($row['number']) === 'total') {
            return null;  // skip this row entirely
        }

        $exists = Unit::where('building_id', $this->building->id)
            ->where('unit_no',    $row['number'])
            ->exists();

        if ($exists) {
            return null;
        }

        self::$totalCount++;

        return new Unit([
            'prop_type'         => 'Residential',
            'unit_type'         => $row['use'],
            'unit_no'           => $row['number'],
            'floor'             => $row['floor'],
            'parking'           => 1,
            'pool_jacuzzi'      => '-',
            'internal_square'   => $row['sqm'],
            'external_square'   => $row['sqm2'],
            'furnished'         => false,
            'unit_view'         => '-',
            'price'             => $row['list_price'],
            'min_price'         => $row['min_price'],
            'pre_lunch_price'   => $row['pre_lunch_price'],
            'lunch_price'       => $row['lunch_price'],
            'building_id'       => $this->building->id,
            'status'            => 'Pending',
            'status_changed_at' => Carbon::now(),
        ]);
    }

    /**
     * Insert in batches of 500 for speed.
     */
    public function batchSize(): int { return 500; }

    public function chunkSize(): int { return 500; }
}
