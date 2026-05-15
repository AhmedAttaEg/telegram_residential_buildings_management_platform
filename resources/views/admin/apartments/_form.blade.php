<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <x-form.select
            :label="'Building'"
            name="building_id"
            :value="$apartment->building_id"
            :options="$buildings->pluck('name', 'id')->all()"
        />
        <x-form.input :label="'Unit number'" name="unit_number" :value="$apartment->unit_number" required />
        <x-form.select :label="'Occupancy status'" name="occupancy_status" :value="$apartment->occupancy_status ?: 'vacant'" :options="['vacant' => 'Vacant', 'occupied' => 'Occupied']" />
        <x-form.select :label="'Status'" name="status" :value="$apartment->status ?: 'active'" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
        <x-form.input :label="'Floor number'" name="floor_number" type="number" min="0" :value="$apartment->floor_number" />
        <x-form.input :label="'Unit type'" name="unit_type" :value="$apartment->unit_type" />
        <x-form.input :label="'Bedrooms'" name="bedrooms" type="number" min="0" :value="$apartment->bedrooms" />
        <x-form.input :label="'Bathrooms'" name="bathrooms" type="number" min="0" :value="$apartment->bathrooms" />
        <x-form.input :label="'Area value'" name="area_value" type="number" step="0.01" min="0" :value="$apartment->area_value" />
        <x-form.input :label="'Area unit'" name="area_unit" :value="$apartment->area_unit ?: 'sqm'" />
    </div>

    <div class="flex justify-end">
        <x-form.submit-button>{{ $submit }}</x-form.submit-button>
    </div>
</form>
