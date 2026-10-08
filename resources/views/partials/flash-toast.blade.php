@if (session('status') || session('error'))
    @php
        $tmFlashType = session('error') ? 'error' : 'success';
        $tmFlashMessage = session('error') ?: session('status');
    @endphp
    <div class="tm-toast-wrap">
        <div class="tm-toast tm-toast-{{ $tmFlashType }}" id="tmFlashToast" role="alert">
            <span class="tm-toast-icon">
                @if ($tmFlashType === 'success')
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                @endif
            </span>
            <span class="tm-toast-message">{{ $tmFlashMessage }}</span>
            <button type="button" class="tm-toast-close" aria-label="Close" onclick="this.closest('.tm-toast-wrap').remove()">&times;</button>
            <span class="tm-toast-bar"></span>
        </div>
    </div>

    <script>
        (function () {
            var wrap = document.currentScript.previousElementSibling;
            if (!wrap) { return; }
            setTimeout(function () {
                wrap.querySelector('.tm-toast').classList.add('tm-toast-hide');
                setTimeout(function () { wrap.remove(); }, 300);
            }, 4000);
        })();
    </script>
@endif
