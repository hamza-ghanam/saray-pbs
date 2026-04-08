<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'group'       => $this->group,
            'key'         => $this->key,
            'value'       => $this->value,
            'typed_value' => $this->typed_value,
            'type'        => $this->type,
            'description' => $this->description,
            'is_public'   => $this->is_public,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
