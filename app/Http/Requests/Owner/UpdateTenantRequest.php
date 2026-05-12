<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
        $tenant = $this->route('tenant');
        $tenantId = is_object($tenant) ? $tenant->getKey() : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenantId)],
            'subscription_plan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
            'grace_ends_at' => ['sometimes', 'nullable', 'date'],
            'subscription_ends_at' => ['sometimes', 'nullable', 'date'],
            'feature_flags' => ['sometimes', 'array'],
            'brand_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'primary_color' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
