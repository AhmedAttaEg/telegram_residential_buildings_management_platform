<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <x-form.input :label="'Name'" name="name" :value="$tenant->name" required />
        <x-form.input :label="'Slug'" name="slug" :value="$tenant->slug" required />
        <x-form.select
            :label="'Status'"
            name="status"
            :value="$tenant->status ?: 'active'"
            :options="['active' => 'Active', 'suspended' => 'Suspended']"
        />
        <x-form.select
            :label="'Subscription status'"
            name="subscription_status"
            :value="$tenant->subscription_status ?: 'trial'"
            :options="['trial' => 'Trial', 'active' => 'Active', 'grace' => 'Grace', 'expired' => 'Expired', 'suspended' => 'Suspended']"
        />
        <x-form.input :label="'Subscription plan'" name="subscription_plan" :value="$tenant->subscription_plan" />
        <x-form.input :label="'Brand name'" name="brand_name" :value="$tenant->brand_name" />
        <x-form.input :label="'Primary color'" name="primary_color" :value="$tenant->primary_color" />
        <x-form.input :label="'Logo path'" name="logo_path" :value="$tenant->logo_path" />
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <h2 class="text-lg font-semibold text-stone-900">Feature flags</h2>
        </div>
        <div class="app-card-body grid gap-4 md:grid-cols-2">
            @foreach ($featureDefaults as $feature => $enabled)
                <label class="flex items-center justify-between rounded-xl border border-stone-200 px-4 py-3">
                    <span class="font-medium text-stone-800">{{ str($feature)->replace('_', ' ')->headline() }}</span>
                    <input
                        type="checkbox"
                        name="feature_flags[{{ $feature }}]"
                        value="1"
                        @checked(old("feature_flags.{$feature}", $enabled))
                        class="h-4 w-4 rounded border-stone-300 text-amber-500 focus:ring-amber-200"
                    >
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <x-form.submit-button>{{ $submit }}</x-form.submit-button>
    </div>
</form>
