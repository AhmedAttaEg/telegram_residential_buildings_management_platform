<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'subscription_status' => ['nullable', 'string', 'in:trial,active,grace,expired,suspended'],
            'subscription_plan' => ['nullable', 'string', 'max:255'],
            'trial_ends_at' => ['nullable', 'date'],
            'grace_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'feature_flags' => ['nullable', 'array'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:32'],
        ];
    }
}
