<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;
        $buildingId = (int) $this->input('building_id');

        return [
            'building_id' => ['required', 'integer', Rule::exists('buildings', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'unit_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('apartments', 'unit_number')->where(fn ($query) => $query->where('building_id', $buildingId)),
            ],
            'occupancy_status' => ['required', 'string', 'in:vacant,occupied'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'floor_number' => ['nullable', 'integer', 'min:0'],
            'unit_type' => ['nullable', 'string', 'max:255'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'area_value' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:16'],
        ];
    }
}
