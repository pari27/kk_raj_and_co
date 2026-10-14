@php
    $rejected = ($notification->title ?? '') === 'Document rejected';
    $icons = [
        'documents' => ['#e8effd', '#1d4fb8', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline>'],
        'rejected' => ['#fde8e8', '#c0392b', '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'],
        'tickets' => ['#efe9fb', '#5b3fb3', '<path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"></path><circle cx="7" cy="7" r="1.4"></circle>'],
        'payments' => ['#e3f4e8', '#1f7a3a', '<path d="M6 3h12"></path><path d="M6 8h12"></path><path d="M6 13h5a4 4 0 0 0 0-8"></path><path d="M9 13l7 8"></path>'],
        'enquiries' => ['#e3f5f1', '#15806b', '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>'],
        'clients' => ['#fde8f1', '#b0245f', '<circle cx="12" cy="8" r="4"></circle><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"></path>'],
        'security' => ['#fdf0dc', '#b26a00', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>'],
    ];
    [$bg, $fg, $path] = $icons[$rejected ? 'rejected' : $notification->type];
    $size = $size ?? 38;
@endphp
<span class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: {{ $size }}px; height: {{ $size }}px; background: {{ $bg }}; color: {{ $fg }};">
    <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size * .45 }}" height="{{ $size * .45 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
</span>
