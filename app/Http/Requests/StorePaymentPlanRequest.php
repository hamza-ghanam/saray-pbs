<?php

namespace App\Http\Requests;

use App\Models\PaymentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class StorePaymentPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

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

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dld_fee_percentage' => ['required', 'numeric', 'between:0,100'],
            'admin_fee' => ['required', 'numeric', 'min:0'],
            'handover_percentage' => ['required', 'numeric', 'between:0,100'],
            'post_handover_enabled' => ['required', 'boolean'],
            'post_handover_months' => ['nullable', 'integer', 'min:0'],

            'blocks' => ['required', 'array', 'min:1'],
            'blocks.*.type' => ['required', 'in:single,repeat'],
            'blocks.*.description' => ['required', 'string', 'max:255'],
            'blocks.*.percentage' => ['required', 'numeric', 'gt:0', 'lt:100'],

            // single-type blocks
            'blocks.*.date' => [
                'exclude_unless:blocks.*.type,single',
                'required_without:blocks.*.offset',
                'date'
            ],
            'blocks.*.offset' => [
                'exclude_unless:blocks.*.type,single',
                'required_without:blocks.*.date',
                'array'
            ],
            'blocks.*.offset.months' => ['nullable', 'integer', 'min:0'],
            'blocks.*.offset.years' => ['nullable', 'integer', 'min:0'],

            // repeat-type blocks (no count from client)
            'blocks.*.start_offset' => ['required_if:blocks.*.type,repeat', 'array'],
            'blocks.*.start_offset.months' => ['nullable', 'integer', 'min:0'],
            'blocks.*.start_offset.years' => ['nullable', 'integer', 'min:0'],
            'blocks.*.frequency' => ['required_if:blocks.*.type,repeat', 'array'],
            'blocks.*.frequency.months' => ['nullable', 'integer', 'min:1'],
            'blocks.*.frequency.years' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $v) {
            $blocks = collect($this->input('blocks'));

            $handoverPct = (float) $this->input('handover_percentage', 0);
            $postHandoverEnabled = $this->boolean('post_handover_enabled');
            $postHandoverMonths = (int) $this->input('post_handover_months', 0);

            $singlesPct = (float) $blocks
                ->where('type', 'single')
                ->sum('percentage');

            if (($singlesPct + $handoverPct) > 100) {
                $v->errors()->add(
                    'handover_percentage',
                    'Singles total percentage plus handover percentage must not exceed 100.'
                );
            }

            if ($postHandoverEnabled && $postHandoverMonths < 1) {
                $v->errors()->add(
                    'post_handover_months',
                    'Post-handover months must be at least 1 when post-handover is enabled.'
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

            foreach (($this->input('blocks', [])) as $index => $block) {
                if (($block['type'] ?? null) === 'single') {
                    $hasDate = !empty($block['date']);
                    $hasOffset = !empty($block['offset']);

                    if (!$hasDate && !$hasOffset) {
                        $v->errors()->add(
                            "blocks.$index.date",
                            'Single block must contain either date or offset.'
                        );
                    }
                }

                if (($block['type'] ?? null) === 'repeat') {
                    if (empty($block['start_offset'])) {
                        $v->errors()->add(
                            "blocks.$index.start_offset",
                            'Repeat block must contain start_offset.'
                        );
                    }

                    if (empty($block['frequency'])) {
                        $v->errors()->add(
                            "blocks.$index.frequency",
                            'Repeat block must contain frequency.'
                        );
                    }
                }
            }

            // 2) Ensure each single block's resolved date is unique
            $dates = $blocks->map(function ($b) {
                $dt = Carbon::now();
                if ($b['type'] === 'single') {
                    if (!empty($b['date'])) {
                        return Carbon::parse($b['date'])->toDateString();
                    }
                    $dt = $dt
                        ->addMonthsNoOverflow($b['offset']['months'] ?? 0)
                        ->addYearsNoOverflow($b['offset']['years'] ?? 0);
                    return $dt->toDateString();
                }
                // skip repeats here
                return null;
            })->filter();

            if ($dates->unique()->count() !== $dates->count()) {
                $v->errors()->add(
                    'blocks',
                    'Each single block must resolve to a unique start date.'
                );
            }
        });
    }
}
