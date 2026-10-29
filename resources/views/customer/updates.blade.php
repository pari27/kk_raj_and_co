@extends('layouts.customer')

@section('title', 'Updates — ' . config('app.firm_name'))

@section('content')

<x-page-header title="Updates" subtitle="Messages about your services" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('customer.dashboard')], ['label' => 'Updates']]">
    <x-slot:actions>
        <form method="POST" action="{{ route('customer.updates.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary" @disabled(! $hasUnread)>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Mark all as read
            </button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="p-card">
    <div class="p-card-head navy">
        <span>Notifications</span>
    </div>

    @forelse ($groups as $group => $updates)
        <div class="p-group">{{ strtoupper($group) }}</div>

        @foreach ($updates as $update)
            @php [$tone, $icon] = $update->look(); @endphp
            <a href="{{ route('customer.updates.open', $update) }}" class="js-update-row p-upd {{ $update->isUnread() ? '' : 'read' }}">
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

@push('scripts')
<script>
    // Opening an update marks it read, so take it off this list right away,
    // even when it was opened in another tab and this page stays behind.
    document.querySelectorAll('.js-update-row').forEach(function (row) {
        ['click', 'auxclick'].forEach(function (name) {
            row.addEventListener(name, function () {
                setTimeout(function () {
                    var label = row.previousElementSibling;
                    var next = row.nextElementSibling;
                    row.remove();

                    // A day heading with nothing under it goes too.
                    var emptyHeading = label && !label.classList.contains('js-update-row')
                        && (!next || !next.classList.contains('js-update-row'));
                    if (emptyHeading) { label.remove(); }

                    // The update has been marked read by now, so bring the bell's number up to date.
                    setTimeout(function () { if (window.tmCheckNow) { window.tmCheckNow(); } }, 800);
                }, 150);
            });
        });
    });
</script>
@endpush
