<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <x-form.input :label="'Name'" name="name" :value="$building->name" required />
        <x-form.input :label="'Slug'" name="slug" :value="$building->slug" required />
        <x-form.input :label="'Code'" name="code" :value="$building->code" />
        <x-form.select :label="'Status'" name="status" :value="$building->status ?: 'active'" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
        <x-form.input :label="'Country'" name="country" :value="$building->country" />
        <x-form.input :label="'City'" name="city" :value="$building->city" />
        <x-form.input :label="'Area'" name="area" :value="$building->area" />
        <x-form.input :label="'Postal code'" name="postal_code" :value="$building->postal_code" />
    </div>

    <x-form.input :label="'Address line 1'" name="address_line_1" :value="$building->address_line_1" />
    <x-form.input :label="'Address line 2'" name="address_line_2" :value="$building->address_line_2" />

    <div class="flex justify-end">
        <x-form.submit-button>{{ $submit }}</x-form.submit-button>
    </div>
</form>
