<?php

namespace App\Http\Requests\Owner;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $featureLimits = $this->input('feature_limits_json');

        $decoded = null;

        if (is_string($featureLimits) && trim($featureLimits) !== '') {
            $decoded = json_decode($featureLimits, true);
        }

        $this->merge([
            'feature_limits' => $decoded,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('subscription_plans', 'slug')],
            'status' => ['required', 'string', Rule::in(SubscriptionPlan::statuses())],
            'billing_cycle' => ['required', 'string', Rule::in(SubscriptionPlan::billingCycles())],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'feature_limits_json' => ['nullable', 'string'],
            'feature_limits' => ['nullable', 'array'],
        ];
    }
}
