@props(['items' => []])
<nav aria-label="breadcrumb" class="mb-3" style="background: #fff; border: 1px solid var(--tm-surface-border); border-top: 0; border-left: 0; border-right: 0; border-radius: 0; padding: .4rem 1.5rem; margin-left: -1.5rem; margin-right: -1.5rem; width: calc(100% + 3rem);">
    <ol class="d-flex flex-wrap align-items-center gap-2 mb-0" style="list-style: none; padding: 0; font-size: .72rem;">
        @foreach ($items as $i => $item)
            @if ($i > 0)
                <li class="d-flex" style="color: #c3c8d1;" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </li>
            @endif
            <li class="d-flex align-items-center gap-1">
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="text-decoration-none d-flex align-items-center gap-1" style="color: {{ $i === 0 ? 'var(--tm-accent)' : 'var(--tm-muted)' }}; font-weight: {{ $i === 0 ? '600' : '500' }};">
                        @if ($i === 0)
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="d-flex align-items-center gap-1" style="color: {{ $loop->last ? 'var(--tm-text)' : 'var(--tm-muted)' }};">
                        @if ($i === 0)
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        @endif
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
