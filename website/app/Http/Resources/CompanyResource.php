<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'status' => $this->status,
            'logo_url' => $this->logo_path ? asset($this->logo_path) : null,
            'headquarters' => collect([$this->city, $this->state, $this->country_code])
                ->filter()->implode(', ') ?: null,
            'website' => $this->website,
            'industry' => $this->industry,
            'verified_at' => $this->verified_at?->toIso8601String(),
        ];
    }
}
