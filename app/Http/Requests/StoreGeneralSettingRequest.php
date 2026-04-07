<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group'       => ['nullable', 'string', 'max:255'],
            'key'         => ['required', 'string', 'max:255', 'unique:general_settings,key'],
            'value'       => ['nullable'],
            'type'        => ['required', 'string', 'in:string,integer,float,boolean,json'],
            'description' => ['nullable', 'string'],
            'is_public'   => ['nullable', 'boolean'],
        ];
    }
}
