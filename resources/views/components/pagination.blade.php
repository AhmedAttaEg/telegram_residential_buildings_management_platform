@props(['paginator'])

@if ($paginator->hasPages())
    <div class="mt-4">
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
