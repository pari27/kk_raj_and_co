@props(['status'])
@php
    $resolved = $status instanceof \App\Enums\PaymentStatus ? $status : \App\Enums\PaymentStatus::tryFrom($status);
    $variant = $resolved?->badgeVariant() ?? 'secondary';
    $label = $resolved?->value ?? $status;
@endphp
<span {{ $attributes->merge(['class' => "badge rounded-pill text-bg-{$variant} fw-normal px-3 py-2"]) }}>{{ $label }}</span>
