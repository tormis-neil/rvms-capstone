<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Agency
 */
class AgencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'license_expiry_warning_days' => $this->license_expiry_warning_days,
        ];
    }
}
