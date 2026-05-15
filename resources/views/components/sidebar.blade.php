@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $links = [];

    if ($user?->isPlatformOwner() && $user->tenant_id === null) {
        $links[] = ['label' => __('web.nav.owner_dashboard'), 'route' => 'owner.dashboard', 'active' => request()->routeIs('owner.dashboard')];
        $links[] = ['label' => 'Tenants', 'route' => 'owner.tenants.index', 'active' => request()->routeIs('owner.tenants.*')];
        $links[] = ['label' => 'Subscription Plans', 'route' => 'owner.subscription-plans.index', 'active' => request()->routeIs('owner.subscription-plans.*')];
        $links[] = ['label' => 'Audit Logs', 'route' => 'owner.audit-logs.index', 'active' => request()->routeIs('owner.audit-logs.*')];
        $links[] = ['label' => 'System Health', 'route' => 'owner.system-health', 'active' => request()->routeIs('owner.system-health')];
    }

    if ($user !== null && app(\App\Support\WebDashboardResolver::class)->canAccessTenantAdmin($user)) {
        $links[] = ['label' => __('web.nav.admin_dashboard'), 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.*')];
    }

    if ($user !== null && app(\App\Support\WebDashboardResolver::class)->canAccessResidentPortal($user)) {
        $links[] = ['label' => __('web.nav.resident_dashboard'), 'route' => 'resident.dashboard', 'active' => request()->routeIs('resident.*')];
    }
@endphp

<aside class="hidden w-72 shrink-0 border-e border-stone-200 bg-stone-950 text-stone-100 lg:block">
    <div class="flex h-full flex-col">
        <div class="border-b border-stone-800 px-6 py-6">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-300">{{ __('web.app_name') }}</p>
            @if ($user?->tenant !== null)
                <p class="mt-2 text-sm text-stone-300">{{ $user->tenant->name }}</p>
            @endif
        </div>

        <nav class="flex-1 space-y-2 px-4 py-6">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'block rounded-xl px-4 py-3 text-sm font-medium transition',
                        'bg-amber-400 text-stone-950' => $link['active'],
                        'text-stone-200 hover:bg-stone-900 hover:text-white' => ! $link['active'],
                    ])
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</aside>
