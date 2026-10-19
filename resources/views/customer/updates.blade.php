@extends('layouts.customer')

@section('title', 'Updates — ' . config('app.firm_name'))

@section('content')
@php
    // Which icon and colour an update gets, from what it is about.
    $look = function ($update) {
        $title = strtolower($update->title);

        return match (true) {
            str_contains($title, 'verified') => ['green', 'check'],
            str_contains($title, 're-upload') => ['red', 'upload'],
            str_contains($title, 'completed') => ['green', 'download'],
            $update->type === 'payment' => ['red', 'rupee'],
            default => ['gray', 'list'],
        };
    };
@endphp

<x-page-header title="Updates" subtitle="Messages about your services" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('customer.dashboard')], ['label' => 'Updates']]" />

<div class="p-card">
    <div class="p-card-head navy">
        <span>Notifications</span>
        @if ($hasUnread)
            <form method="POST" action="{{ route('customer.updates.read-all') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-link p-0 text-white u-43a94f">Mark all read</button>
            </form>
        @endif
    </div>

    @forelse ($groups as $group => $updates)
        <div class="p-group">{{ strtoupper($group) }}</div>

        @foreach ($updates as $update)
            @php [$tone, $icon] = $look($update); @endphp
            <a href="{{ route('customer.updates.open', $update) }}" class="p-upd {{ $update->isUnread() ? '' : 'read' }}">
                <span class="ico {{ $tone }}"><x-portal-icon :name="$icon" :size="18" /></span>
                <div>
                    <div class="tt">{{ $update->title }}</div>
                    @if ($update->body)<div class="bd">{{ $update->body }}</div>@endif
                </div>
                <div class="tm">
                    {{ $group === 'Earlier' ? $update->created_at->format('j M') : $update->created_at->format('g:i A') }}
                    @if ($update->isUnread())<i aria-label="Unread"></i>@endif
                </div>
            </a>
        @endforeach
    @empty
        <div class="p-card-body text-center tm-muted u-8ae449">Nothing new yet. We'll tell you here when something changes.</div>
    @endforelse
</div>
@endsection
