<?php
namespace App\Http\Requests;

use App\Models\PaymentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StorePaymentPlanRequest extends FormRequest
{
    public function authorize() { return true; }

    /**
     * If someone sent "blocks" as a JSON string, decode it to array.
     */
    protected function prepareForValidation(): void
    {
        if (
            $this->has('blocks') &&
            is_string($this->blocks)
        ) {
            $this->merge([
                'blocks' => json_decode($this->blocks, true) ?? []
            ]);
        }
    }

    public function rules()
    {
        return [
            'name'                  => ['required','string','max:255'],
            'dld_fee_percentage'    => ['required','numeric','between:0,100'],
            'admin_fee'             => ['required','numeric','min:0'],
            'EOI'                   => ['required','numeric','min:0'],
            'is_default'            => ['sometimes','boolean'],

            'blocks'                => ['required','array','min:2'],
            'blocks.*.type'         => ['required','in:single,repeat'],
            'blocks.*.description'  => ['required','string','max:255'],
            'blocks.*.percentage'   => ['required','numeric','gt:0','lt:100'],

            // ——— single-type blocks must have EITHER date OR offset ———
            'blocks.*.date'         => [
                'exclude_unless:blocks.*.type,single',
                'required_without:blocks.*.offset',
                'date'
            ],
            'blocks.*.offset'       => [
                'exclude_unless:blocks.*.type,single',
                'required_without:blocks.*.date',
                'array'
            ],
            'blocks.*.offset.months'=> ['nullable','integer','min:0'],
            'blocks.*.offset.years' => ['nullable','integer','min:0'],

            // ——— repeat-type blocks must have start_offset, frequency & count ———
            'blocks.*.start_offset'       => ['required_if:blocks.*.type,repeat','array'],
            'blocks.*.start_offset.months'=> ['nullable','integer','min:0'],
            'blocks.*.start_offset.years' => ['nullable','integer','min:0'],
            'blocks.*.frequency'          => ['required_if:blocks.*.type,repeat','array'],
            'blocks.*.frequency.months'   => ['nullable','integer','min:1'],
            'blocks.*.frequency.years'    => ['nullable','integer','min:1'],
            'blocks.*.count'              => ['required_if:blocks.*.type,repeat','integer','min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $blocks = collect($this->input('blocks'));

            // 1) Sum percentages * count for repeats
            $totalPct = $blocks->reduce(function ($sum, $b) {
                return $sum + (
                    $b['type'] === 'repeat'
                        ? ($b['percentage'] * $b['count'])
                        : $b['percentage']
                    );
            }, 0);

            if ($totalPct !== 100) {
                $v->errors()->add(
                    'blocks',
                    "Total of all percentages must equal 100 (got {$totalPct}%)."
                );
            }

            if ($this->boolean('is_default')) {
                $exists = PaymentPlan::where('is_default', true)
                    ->when($this->route('id'), fn($q, $id) => $q->where('id', '!=', $id))
                    ->exists();

                if ($exists) {
                    $v->errors()->add(
                        'is_default',
                        'There is already another default payment plan—only one may be default.'
                    );
                }
            }

            // 2) Ensure each block’s start date is unique
            $dates = $blocks->map(function ($b) {
                $dt = Carbon::now();
                if ($b['type'] === 'single') {
                    if (!empty($b['date'])) {
                        return Carbon::parse($b['date'])->toDateString();
                    }
                    $dt = $dt
                        ->addMonths($b['offset']['months'] ?? 0)
                        ->addYears ($b['offset']['years']  ?? 0);
                } else {
                    $dt = $dt
                        ->addMonths($b['start_offset']['months'] ?? 0)
                        ->addYears ($b['start_offset']['years']  ?? 0);
                }
                return $dt->toDateString();
            });

            if ($dates->unique()->count() !== $dates->count()) {
                $v->errors()->add(
                    'blocks',
                    'Each block must resolve to a unique start date.'
                );
            }
        });
    }
}
