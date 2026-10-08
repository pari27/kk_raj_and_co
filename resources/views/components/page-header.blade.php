@props(['title', 'subtitle' => null, 'divider' => true, 'breadcrumbs' => null])
@if ($breadcrumbs)
    <x-breadcrumbs :items="$breadcrumbs" />
@endif
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 {{ $divider ? 'tm-divider-gold' : '' }}">
    <div>
        <h1 class="tm-serif fw-bold mb-1" style="font-size: 1.15rem;">{{ $title }}</h1>
        @if($subtitle)
            <p class="tm-muted mb-0" style="font-size: .8rem;">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
