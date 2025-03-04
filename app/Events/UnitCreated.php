<?php

namespace App\Events;

use App\Models\Unit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnitCreated
{
    use Dispatchable, SerializesModels;

    public $unit;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Unit $unit
     * @return void
     */
    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
    }
}
