<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settingId = $this->route('general_setting')?->id ?? $this->route('general_setting');

        return [
            'group'       => ['sometimes', 'string', 'max:255'],
            'key'         => ['sometimes', 'string', 'max:255', Rule::unique('general_settings', 'key')->ignore($settingId)],
            'value'       => ['nullable'],
            'type'        => ['sometimes', 'string', 'in:string,integer,float,boolean,json'],
            'description' => ['nullable', 'string'],
            'is_public'   => ['sometimes', 'boolean'],
        ];
    }
}
