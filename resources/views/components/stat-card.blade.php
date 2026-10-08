@props(['label', 'value', 'valueClass' => '', 'footnote' => null])
<div class="tm-card p-3 h-100">
    <div class="tm-muted small mb-2">{{ $label }}</div>
    <div class="h3 tm-serif fw-bold mb-0 {{ $valueClass }}">{{ $value }}</div>
    @isset($footnote)
        <div class="tm-muted small mt-2">{{ $footnote }}</div>
    @endisset
</div>
