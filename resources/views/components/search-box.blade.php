@props(['id'])

{{-- The one search field used on every list page, so they all look and read the same. --}}
<div class="search-box">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-box-icon" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    <input type="text" id="{{ $id }}" class="search-box-input" placeholder="Search your details here" aria-label="Search your details here" autocomplete="off">
</div>
