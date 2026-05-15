<x-app-layout :title="$auditLog->event" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Audit Logs', 'url' => route('owner.audit-logs.index')], ['label' => $auditLog->event]]">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="app-card">
            <div class="app-card-header">
                <h2 class="text-lg font-semibold text-stone-900">Audit summary</h2>
            </div>
            <div class="app-card-body space-y-4 text-sm">
                <div><span class="font-semibold text-stone-900">Event:</span> {{ $auditLog->event }}</div>
                <div><span class="font-semibold text-stone-900">Tenant:</span> {{ $auditLog->tenant?->name ?: 'Platform' }}</div>
                <div><span class="font-semibold text-stone-900">Actor:</span> {{ $auditLog->actor?->email ?? $auditLog->actor_type ?? 'System' }}</div>
                <div><span class="font-semibold text-stone-900">Subject:</span> {{ $auditLog->subject_type }}#{{ $auditLog->subject_id }}</div>
                <div><span class="font-semibold text-stone-900">Created:</span> {{ $auditLog->created_at?->toDateTimeString() }}</div>
            </div>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <h2 class="text-lg font-semibold text-stone-900">Metadata</h2>
            </div>
            <div class="app-card-body">
                <pre class="overflow-x-auto rounded-xl bg-stone-950 p-4 text-xs text-stone-100">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <h2 class="text-lg font-semibold text-stone-900">Old values</h2>
            </div>
            <div class="app-card-body">
                <pre class="overflow-x-auto rounded-xl bg-stone-950 p-4 text-xs text-stone-100">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <h2 class="text-lg font-semibold text-stone-900">New values</h2>
            </div>
            <div class="app-card-body">
                <pre class="overflow-x-auto rounded-xl bg-stone-950 p-4 text-xs text-stone-100">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>
