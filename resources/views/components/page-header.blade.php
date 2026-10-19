@props(['title', 'subtitle' => null, 'divider' => true, 'breadcrumbs' => null])
@if ($breadcrumbs)
    <x-breadcrumbs :items="$breadcrumbs" />
@endif
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 {{ $divider ? 'tm-divider-gold' : '' }}">
    <div>
        <h1 class="tm-serif fw-bold mb-1 u-e83e11">{{ $title }}</h1>
        @if($subtitle)
            <p class="tm-muted mb-0 u-5a305f">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
