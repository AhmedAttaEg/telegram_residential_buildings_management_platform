<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <x-form.input :label="'Name'" name="name" :value="$plan->name" required />
        <x-form.input :label="'Slug'" name="slug" :value="$plan->slug" required />
        <x-form.select :label="'Status'" name="status" :value="$plan->status ?: 'active'" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
        <x-form.select :label="'Billing cycle'" name="billing_cycle" :value="$plan->billing_cycle ?: 'monthly'" :options="['monthly' => 'Monthly', 'annual' => 'Annual']" />
        <x-form.input :label="'Price amount'" name="price_amount" type="number" step="0.01" min="0" :value="$plan->price_amount" required />
        <x-form.input :label="'Currency'" name="currency" :value="$plan->currency ?: 'EGP'" required />
        <x-form.input :label="'Trial days'" name="trial_days" type="number" min="0" :value="$plan->trial_days" />
    </div>

    <x-form.textarea :label="'Description'" name="description" :value="$plan->description" />
    <x-form.textarea :label="'Feature limits (JSON)'" name="feature_limits_json" :value="$plan->feature_limits !== null ? json_encode($plan->feature_limits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null" />

    <div class="flex justify-end">
        <x-form.submit-button>{{ $submit }}</x-form.submit-button>
    </div>
</form>
