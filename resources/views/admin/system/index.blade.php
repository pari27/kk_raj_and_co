@extends('layouts.app')

@section('title', 'System — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="System" subtitle="Health and recent errors. Visible to the Super Admin only." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'System']]">
    <x-slot:actions>
        <a href="{{ route('admin.audit-log') }}" class="btn btn-outline-secondary">Audit Log</a>
        <a href="{{ route('super.system.log') }}" class="btn btn-tm-primary">Download full log ({{ number_format($logSize / 1024) }} KB)</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <section class="tm-card p-0 overflow-hidden h-100">
            <div class="tm-section-title">Health</div>
            <table class="table tm-table align-middle mb-0 system-table">
                <tbody>
                    @foreach ($health as $label => $item)
                        <tr>
                            <td class="text-secondary">{{ $label }}</td>
                            <td class="fw-semibold">
                                @if ($item['ok'] === true)<span class="system-dot system-dot-ok"></span>@elseif ($item['ok'] === false)<span class="system-dot system-dot-bad"></span>@endif
                                {{ $item['value'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
    <div class="col-12 col-xl-5">
        <section class="tm-card p-0 overflow-hidden h-100">
            <div class="tm-section-title">In the system</div>
            <table class="table tm-table align-middle mb-0 system-table">
                <tbody>
                    @foreach ($counts as $label => $count)
                        <tr><td class="text-secondary">{{ $label }}</td><td class="fw-semibold text-end">{{ number_format($count) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
</div>

<section class="tm-card p-0 overflow-hidden">
    <div class="tm-section-title d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <span>{{ $showResolved ? 'Resolved errors' : 'Recent errors' }} ({{ count($errors) }})</span>
        <span class="d-flex align-items-center gap-3 small fw-normal">
            @if ($showResolved)
                <a href="{{ route('super.system') }}" class="text-white">Back to open errors</a>
            @else
                @if ($resolvedCount > 0)
                    <a href="{{ route('super.system', ['show' => 'resolved']) }}" class="text-white">Show resolved ({{ $resolvedCount }})</a>
                @endif
                @if (count($errors) > 0)
                    <form method="POST" action="{{ route('super.system.errors.resolve-all') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light py-0">Mark all resolved</button>
                    </form>
                @endif
            @endif
        </span>
    </div>

    @forelse ($errors as $i => $error)
        <div class="system-error">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <span class="system-level system-level-{{ strtolower($error['level']) }}">{{ $error['level'] }}</span>
                    <span class="system-time">{{ $error['time'] }}</span>
                    <div class="system-msg">{{ $error['message'] }}</div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#sysErr{{ $i }}">Details</button>
                    <form method="POST" action="{{ $showResolved ? route('super.system.errors.reopen') : route('super.system.errors.resolve') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="fingerprint" value="{{ $error['fingerprint'] }}">
                        <button type="submit" class="btn btn-sm {{ $showResolved ? 'btn-outline-secondary' : 'btn-outline-success' }}">{{ $showResolved ? 'Show again' : 'Mark resolved' }}</button>
                    </form>
                </div>
            </div>
            <div class="collapse" id="sysErr{{ $i }}">
                <pre class="system-detail">{{ $error['detail'] }}</pre>
            </div>
        </div>
    @empty
        <div class="p-4 text-center text-secondary">
            {{ $showResolved ? 'Nothing has been marked as resolved.' : 'No open errors. Everything looks fine.' }}
        </div>
    @endforelse
</section>
@endsection
