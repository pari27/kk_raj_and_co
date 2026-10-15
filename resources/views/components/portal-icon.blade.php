@props(['name', 'size' => 20])
@php
    $paths = [
        'upload' => '<path d="M12 16V4"></path><polyline points="7 9 12 4 17 9"></polyline><path d="M4 20h16"></path>',
        'download' => '<path d="M12 4v12"></path><polyline points="7 11 12 16 17 11"></polyline><path d="M4 20h16"></path>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>',
        'crosshair' => '<line x1="12" y1="3" x2="12" y2="8"></line><line x1="12" y1="16" x2="12" y2="21"></line><line x1="3" y1="12" x2="8" y2="12"></line><line x1="16" y1="12" x2="21" y2="12"></line>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><polyline points="3 7 12 13 21 7"></polyline>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="13 6 19 12 13 18"></polyline>',
        'chevron-right' => '<polyline points="9 6 15 12 9 18"></polyline>',
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="11 6 5 12 11 18"></polyline>',
        'chevron-left' => '<polyline points="15 6 9 12 15 18"></polyline>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'home' => '<path d="M3 11 12 3l9 8"></path><path d="M5 10v10h14V10"></path>',
        'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"></path>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"></path>',
        'check' => '<polyline points="5 12 10 17 19 7"></polyline>',
        'x' => '<line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4Z"></path><circle cx="12" cy="13" r="3.5"></circle>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><polyline points="14 3 14 8 19 8"></polyline>',
        'rupee' => '<path d="M7 5h10"></path><path d="M7 10h10"></path><path d="M7 15h4a4 4 0 0 0 0-8"></path><path d="M10 15l6 5"></path>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path>',
        'eye' => '<path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3"></circle>',
        'credit-card' => '<rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line>',
        'list' => '<line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><circle cx="4" cy="6" r="1"></circle><circle cx="4" cy="12" r="1"></circle><circle cx="4" cy="18" r="1"></circle>',
        'menu' => '<line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line>',
        'check-square' => '<rect x="3" y="3" width="18" height="18" rx="4"></rect><polyline points="8 12 11 15 16 9"></polyline>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
    ];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>{!! $paths[$name] ?? '' !!}</svg>
