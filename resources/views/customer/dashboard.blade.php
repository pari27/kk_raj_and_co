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
        <div class="p-stat blue">
            <div class="lb">Active services</div>
            <div class="vl">{{ $stats['active'] }}</div>
            <div class="sb">in progress</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="p-stat amber">
            <div class="lb">Documents needed</div>
            <div class="vl">{{ $stats['documents'] }}</div>
            <div class="sb">{{ $stats['documentServices'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="p-stat maroon">
            <div class="lb">Amount due</div>
            <div class="vl">{{ $money($stats['due']) }}</div>
            <div class="sb">{{ $stats['dueServices'] ? 'across '.$stats['dueServices'].' '.($stats['dueServices'] === 1 ? 'service' : 'services') : 'nothing due' }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 d-none d-md-block">
        <div class="p-stat green">
            <div class="lb">Completed</div>
            <div class="vl">{{ $stats['completed'] }}</div>
            <div class="sb">this year</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="{{ $contacts->isNotEmpty() ? 'col-12 col-lg-8' : 'col-12' }}">
        <div class="p-card">
            <div class="p-card-head navy sm"><span>Needs your attention</span></div>

            @forelse ($attention as $item)
                <a href="{{ $item['url'] }}" class="p-attn">
                    <span class="bar {{ $item['tone'] }}"></span>
                    <div>
                        <div class="tt">{{ $item['title'] }}</div>
                        <div class="sb">{{ $item['sub'] }}</div>
                    </div>
                    <span class="chip {{ $item['tone'] }}">{{ $item['action'] }}</span>
                </a>
            @empty
                <div class="p-card-body text-center tm-muted" style="font-size: .85rem;">You're all caught up. Nothing needs your attention.</div>
            @endforelse
        </div>
    </div>

    @if ($contacts->isNotEmpty())
        <div class="col-12 col-lg-4">
            <div class="p-card">
                <div class="p-card-head navy sm"><span>Your contact at {{ config('app.firm_name') }}</span></div>

                @foreach ($contacts as $item)
                    <div class="p-card-body {{ $item['contact'] ? '' : 'p-unassigned' }}" style="{{ $loop->last ? '' : 'border-bottom: 1px solid var(--p-line);' }}">
                        <div class="tm-muted fw-bold text-uppercase" style="font-size: .68rem; letter-spacing: .08em; margin-bottom: .6rem;">For {{ $item['service'] }}</div>

                        @if ($item['contact'])
                            <div class="p-contact">
                                <span class="p-av">{{ $item['contact']['initials'] }}</span>
                                <div>
                                    <div class="fw-bold">{{ $item['contact']['name'] }}</div>
                                    <div class="p-contact-lines">
                                    @if ($item['contact']['phone'])<div class="p-contact-line"><x-portal-icon name="phone" :size="15" /> {{ $item['contact']['phone'] }}</div>@endif
                                    @if ($item['contact']['email'])<div class="p-contact-line"><x-portal-icon name="mail" :size="15" /> {{ $item['contact']['email'] }}</div>@endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="tm-muted small">We'll tell you who is handling this soon.</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@include('customer._services-card')
@endsection
