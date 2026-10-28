@extends('layouts.customer')

@section('title', 'Dashboard — ' . config('app.firm_name'))

@section('content')
@php
    $money = fn ($amount) => '₹'.number_format((float) $amount);
    $firstName = explode(' ', trim($customer->name))[0];
    $firstUpload = $attention->first(fn ($item) => $item['tone'] !== 'blue');
@endphp

<x-page-header title="Welcome, {{ $firstName }}" :subtitle="'Here\'s where your services with '.config('app.firm_name').' stand'" :breadcrumbs="[['label' => 'Dashboard']]">
    @if ($firstUpload)
        <x-slot:actions>
            <a href="{{ $firstUpload['url'] }}" class="pbtn-navy d-none d-md-inline-flex"><x-portal-icon name="upload" :size="16" /> Upload documents</a>
        </x-slot:actions>
    @endif
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3 d-none d-md-block">
        <a href="{{ route('customer.tickets.index') }}" class="p-stat blue">
            <div class="lb">Active services</div>
            <div class="vl">{{ $stats['active'] }}</div>
            <div class="sb">in progress</div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ $firstUpload['url'] ?? route('customer.tickets.index') }}" class="p-stat amber">
            <div class="lb">Documents needed</div>
            <div class="vl">{{ $stats['documents'] }}</div>
            <div class="sb">{{ $stats['documentServices'] }}</div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ route('customer.payments.index') }}" class="p-stat maroon">
            <div class="lb">Amount due</div>
            <div class="vl">{{ $money($stats['due']) }}</div>
            <div class="sb">{{ $stats['dueServices'] ? 'across '.$stats['dueServices'].' '.($stats['dueServices'] === 1 ? 'service' : 'services') : 'nothing due' }}</div>
        </a>
    </div>
    <div class="col-6 col-xl-3 d-none d-md-block">
        <a href="{{ route('customer.tickets.index') }}" class="p-stat green">
            <div class="lb">Completed</div>
            <div class="vl">{{ $stats['completed'] }}</div>
            <div class="sb">this year</div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="p-card">
            <div class="p-card-head navy sm"><span>Needs your attention</span><a href="{{ route('customer.tickets.index') }}">More →</a></div>

            @forelse ($attention->take(7) as $item)
                <a href="{{ $item['url'] }}" class="p-attn">
                    <span class="bar {{ $item['tone'] }}"></span>
                    <div>
                        <div class="tt">{{ $item['title'] }}</div>
                        <div class="sb">{{ $item['sub'] }}</div>
                    </div>
                    <span class="chip {{ $item['tone'] }}">{{ $item['action'] }}</span>
                </a>
            @empty
                <div class="p-card-body text-center tm-muted u-8ae449">You're all caught up. Nothing needs your attention.</div>
            @endforelse
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="p-card">
            <div class="p-card-head navy sm"><span>Recent updates</span><a href="{{ route('customer.updates') }}">All updates →</a></div>

            @forelse ($recentUpdates as $update)
                @php [$tone, $icon] = $update->look(); @endphp
                <a href="{{ route('customer.updates.open', $update) }}" class="p-upd p-upd-sm {{ $update->isUnread() ? '' : 'read' }}">
                    <span class="ico {{ $tone }}"><x-portal-icon :name="$icon" :size="15" /></span>
                    <div class="min-w-0">
                        <div class="tt">{{ $update->title }}</div>
                        <div class="bd">{{ $update->created_at->isToday() ? $update->created_at->format('g:i A') : $update->created_at->format('j M') }}</div>
                    </div>
                    @if ($update->isUnread())<i class="dot" aria-label="Unread"></i>@endif
                </a>
            @empty
                <div class="p-card-body text-center tm-muted u-8ae449">Nothing new yet. We'll tell you here when something changes.</div>
            @endforelse
        </div>

        @php
            $firmPhone = config('app.firm_phone');
            $firmEmail = config('app.firm_email');
            $firmAddress = config('app.firm_address');
        @endphp
        <div class="p-card">
            <div class="p-card-head navy sm"><span>Contact {{ config('app.firm_name') }}</span></div>
            <div class="p-card-body p-firm-contact">
                @if ($firmPhone || $firmEmail)
                    <div class="pair">
                    @if ($firmPhone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $firmPhone) }}" class="line"><x-portal-icon name="phone" :size="16" /> <span><strong>{{ $firmPhone }}</strong></span></a>
                    @endif
                    @if ($firmEmail)
                        <a href="mailto:{{ $firmEmail }}" class="line"><x-portal-icon name="mail" :size="16" /> <span><strong>{{ $firmEmail }}</strong></span></a>
                    @endif
                    </div>
                @endif
                @if ($firmAddress)
                    <div class="line"><x-portal-icon name="home" :size="16" /> <span><strong>{{ $firmAddress }}</strong></span></div>
                @endif
                @unless ($firmPhone || $firmEmail || $firmAddress)
                    <div class="tm-muted small">The firm's contact details will appear here soon.</div>
                @endunless
            </div>
        </div>
    </div>
</div>

@include('customer._services-card')
@endsection
