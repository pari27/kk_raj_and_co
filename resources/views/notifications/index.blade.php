@extends('layouts.app')

@section('title', 'Notifications — ' . config('app.name', 'Task Management'))


@php
    $groups = $notifications->getCollection()->groupBy(function ($n) {
        return $n->created_at->isToday() ? 'Today' : ($n->created_at->isYesterday() ? 'Yesterday' : 'Earlier');
    });
    $typeLabels = \App\Models\AppNotification::TYPES;
    $typeUrl = fn (?string $t) => route('notifications.index', array_filter(['type' => $t]));
@endphp

@section('content')
<x-page-header title="Notifications" subtitle="Updates on documents, services, payments and enquiries" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Notifications']]">
    <x-slot:actions>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-tm-primary" @disabled($unreadCount === 0)>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Mark all as read
            </button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 tm-notif">
    <div class="col-12 col-xl-8">
        <div class="tm-card p-0 overflow-hidden">
            <div class="d-flex flex-wrap gap-2 px-4 py-3 border-bottom">
                <a href="{{ $typeUrl(null) }}" class="btn btn-sm rounded-pill {{ $type === null ? 'btn-dark' : 'btn-outline-secondary' }}">All types</a>
                @foreach ($typeLabels as $key => $label)
                    <a href="{{ $typeUrl($key) }}" class="btn btn-sm rounded-pill {{ $type === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>

            @forelse ($groups as $label => $items)
                <div class="px-4 py-2 small fw-bold tm-muted text-uppercase u-8e4f28">{{ $label }}</div>
                @foreach ($items as $n)
                    <a href="{{ route('notifications.open', $n) }}" class="js-notification-row d-flex gap-3 px-4 py-3 border-bottom text-decoration-none text-reset" style="{{ $n->isUnread() ? 'background: #f5f8ff;' : '' }}">
                        @include('notifications._icon', ['notification' => $n, 'size' => 42])
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-bold">{{ $n->title }}</span>
                                <span class="d-flex align-items-center gap-2 small tm-muted flex-shrink-0">
                                    {{ $label === 'Today' ? $n->created_at->diffForHumans(short: true) : $n->created_at->format('g:i A') }}
                                    <span class="rounded-circle {{ $n->isUnread() ? 'bg-success' : '' }} u-59c4af"></span>
                                </span>
                            </div>
                            <div class="small">{{ $n->renderBody() }}</div>
                            @if ($n->link_text || $n->link_label)
                                <div class="d-flex align-items-center gap-2 mt-2 small fw-bold">
                                    @if ($n->link_text)<span class="text-primary">{{ $n->link_text }}</span>@endif
                                    @if ($n->link_label)<span class="badge bg-dark-subtle text-dark-emphasis">{{ $n->link_label }}</span>@endif
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            @empty
                <div class="text-center tm-muted py-5">You are all caught up. No unread notifications.</div>
            @endforelse

            @if ($notifications->hasPages())
                <div class="px-4 py-3">{{ $notifications->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="tm-card p-0 overflow-hidden mb-3">
            <div class="tm-section-title">Unread by type</div>
            @foreach ($typeLabels as $key => $label)
                @php $count = (int) ($unreadByType[$key] ?? 0); @endphp
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                    @include('notifications._icon', ['notification' => (object) ['type' => $key, 'title' => ''], 'size' => 34])
                    <span class="flex-grow-1">{{ $label }}</span>
                    <span class="fw-bold {{ $count ? '' : 'tm-muted' }}">{{ $count }} new</span>
                </div>
            @endforeach
        </div>

        <div class="tm-card p-0 overflow-hidden">
            <div class="tm-section-title">Notify me about</div>
            <form method="POST" action="{{ route('notifications.preferences') }}">
                @csrf
                @method('PUT')
                @foreach (\App\Models\NotificationPreference::LABELS as $key => $label)
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-3 pe-3 py-2 border-bottom m-0">
                        <label class="form-check-label flex-grow-1" for="pref-{{ $key }}">{{ $label }}</label>
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input class="form-check-input ms-0" type="checkbox" role="switch" id="pref-{{ $key }}" name="{{ $key }}" value="1" @checked($preferences->{$key}) onchange="this.form.submit()">
                    </div>
                @endforeach
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Opening a notification marks it read, so take it off this list right away,
    // even when it was opened in another tab and this page stays behind.
    document.querySelectorAll('.js-notification-row').forEach(function (row) {
        ['click', 'auxclick'].forEach(function (name) {
            row.addEventListener(name, function () {
                setTimeout(function () {
                    var label = row.previousElementSibling;
                    var next = row.nextElementSibling;
                    row.remove();

                    // A day heading with nothing under it goes too.
                    var emptyHeading = label && !label.classList.contains('js-notification-row')
                        && (!next || !next.classList.contains('js-notification-row'));
                    if (emptyHeading) { label.remove(); }

                    // The notification has been marked read by now, so bring the bell's number up to date.
                    setTimeout(function () { if (window.tmCheckNow) { window.tmCheckNow(); } }, 800);
                }, 150);
            });
        });
    });
</script>
@endpush
