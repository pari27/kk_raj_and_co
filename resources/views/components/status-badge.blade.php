@props(['status'])
@php
    $variant = \App\Enums\TicketStatus::tryFrom($status)?->badgeVariant() ?? 'secondary';
@endphp
<span {{ $attributes->merge(['class' => "badge rounded-pill text-bg-{$variant} fw-normal px-3 py-2"]) }}>{{ $status }}</span>
