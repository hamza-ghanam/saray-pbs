<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ContractBilingualRow extends Component
{
    public function __construct(
        public string $labelEn = '',
        public string $labelAr = '',
        public string $valueEn = '',
        public string $valueAr = '',
        public string $index = '',
    ) {}

    public function render()
    {
        return view('components.contract-bilingual-row');
    }
}
