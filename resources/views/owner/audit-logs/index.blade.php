<x-app-layout :title="'Audit Logs'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Audit Logs']]">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-stone-900">Audit logs</h2>
        <p class="text-sm text-stone-500">Review platform and tenant lifecycle changes.</p>
    </div>

    @if ($auditLogs->isEmpty())
        <x-empty-state />
    @else
        <x-data-table>
            <thead class="bg-stone-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Event</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Tenant</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Actor</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($auditLogs as $auditLog)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('owner.audit-logs.show', $auditLog) }}" class="font-medium text-stone-900">{{ $auditLog->event }}</a>
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ $auditLog->tenant?->name ?: 'Platform' }}</td>
                        <td class="px-4 py-3 text-stone-700">{{ $auditLog->actor?->email ?? $auditLog->actor_type ?? 'System' }}</td>
                        <td class="px-4 py-3 text-stone-700">{{ $auditLog->created_at?->toDateTimeString() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$auditLogs" />
    @endif
</x-app-layout>
