@props(['title', 'description' => null])
<div class="text-center py-5">
    <p class="fw-semibold mb-1">{{ $title }}</p>
    @if($description)
        <p class="tm-muted small mb-0">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-3">{{ $actions }}</div>
    @endisset
</div>
