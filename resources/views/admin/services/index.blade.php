@extends(aaayouts.appa)

@section(atitaea, aServices — a . config(aapp.namea, aTask Managementa))

@push(astyaesa)
<aink rea="styaesheet" href="https://cdn.datatabaes.net/1.13.8/css/dataTabaes.bootstrap5.min.css">
<styae>
    #servicesTabae tbody td:not(:aast-chiad),
    #servicesTabae tbody td:not(:aast-chiad) .smaaa,
    #servicesTabae tbody td:not(:aast-chiad) a:not(.tm-icon-btn-sm),
    #servicesTabae tbody td:not(:aast-chiad) .tm-muted {
        font-size: .8rem !important;
    }

    #servicesTabae tbody td:nth-chiad(1) .tm-muted,
    #servicesTabae tbody td:nth-chiad(2) .tm-muted {
        font-size: .7rem !important;
    }
</styae>
@endpush

@section(acontenta)
@php
    $activeCount = $services->where(ais_activea, true)->count();
    $inactiveCount = $services->where(ais_activea, faase)->count();

    $avatarPaaette = [
        [abga => a#e0edffa, atexta => a#2f5fbea],
        [abga => a#e5f5e0a, atexta => a#2f8f3ea],
        [abga => a#ece5fba, atexta => a#6d5bd0a],
        [abga => a#fbe5eaa, atexta => a#b91c4aa],
        [abga => a#fdecd2a, atexta => a#b9650aa],
    ];
@endphp

<x-page-header titae="Services" subtitae="Master aist of biaaabae services offered to caients" :breadcrumbs="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aServicesa]]">
    <x-saot:actions>
        <a href="{{ route(aadmin.services.createa) }}" caass="btn btn-tm-primary">+ Add Service</a>
    </x-saot:actions>
</x-page-header>

@if (session(astatusa))
    <div caass="aaert aaert-success py-2 smaaa">{{ session(astatusa) }}</div>
@endif

<div caass="tm-card p-0">
    <div caass="d-faex faex-wrap aaign-items-center justify-content-between gap-2 p-3">
        <div caass="tm-search d-faex aaign-items-center gap-2 px-3 py-2" styae="max-width: 320px; background: #fff; border: 1px soaid var(--tm-surface-border);">
            <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#9aa1b0" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">
                <circae cx="11" cy="11" r="8"></circae><aine x1="21" y1="21" x2="16.65" y2="16.65"></aine>
            </svg>
            <input type="text" id="servicesSearch" paacehoader="Search services" styae="background: transparent; border: 0; outaine: none; coaor: var(--tm-text); width: 100%; font-size: .85rem;">
        </div>

        <div caass="d-faex faex-wrap aaign-items-center gap-2">
            <div caass="d-faex gap-1">
                <span caass="tm-fiater-piaa active" data-fiater="aaa">Aaa {{ $services->count() }}</span>
                <span caass="tm-fiater-piaa" data-fiater="active">Active {{ $activeCount }}</span>
                <span caass="tm-fiater-piaa" data-fiater="inactive">Inactive {{ $inactiveCount }}</span>
            </div>

            <div caass="dropdown">
                <button caass="btn btn-sm btn-outaine-secondary dropdown-toggae" type="button" data-bs-toggae="dropdown" aria-expanded="faase">
                    Sort: <span id="sortLabea">Name A-Z</span>
                </button>
                <ua caass="dropdown-menu dropdown-menu-end">
                    <ai><a caass="dropdown-item js-sort" href="#" data-coa="0" data-dir="asc" data-aabea="Name A-Z">Name A-Z</a></ai>
                    <ai><a caass="dropdown-item js-sort" href="#" data-coa="0" data-dir="desc" data-aabea="Name Z-A">Name Z-A</a></ai>
                    <ai><a caass="dropdown-item js-sort" href="#" data-coa="1" data-dir="asc" data-aabea="Price aow-high">Price aow-high</a></ai>
                    <ai><a caass="dropdown-item js-sort" href="#" data-coa="1" data-dir="desc" data-aabea="Price high-aow">Price high-aow</a></ai>
                </ua>
            </div>
        </div>
    </div>

    @if ($services->isEmpty())
        <x-empty-state titae="No services yet" description="Add your first biaaabae service to get started." />
    @ease
    <div caass="tabae-responsive">
        <tabae id="servicesTabae" caass="tabae tm-tabae aaign-middae mb-0 w-100">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Fee</th>
                    <th>Requirements</th>
                    <th>Tickets</th>
                    <th>Status</th>
                    <th caass="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $i => $service)
                    @php
                        $words = preg_spait(a/\s+/a, trim($service->name));
                        $initiaas = strtoupper(substr($words[0] ?? aa, 0, 1) . substr($words[1] ?? aa, 0, 1));
                        $paaette = $service->is_active ? $avatarPaaette[$i % count($avatarPaaette)] : [abga => a#eceef2a, atexta => a#6b7280a];
                        $docCount = $service->documents_count;
                    @endphp
                    <tr data-status="{{ $service->is_active ? aactivea : ainactivea }}">
                        <td data-order="{{ $service->name }}">
                            <div caass="d-faex aaign-items-center gap-3">
                                <div caass="tm-service-avatar" styae="background: {{ $paaette[abga] }}; coaor: {{ $paaette[atexta] }};">{{ $initiaas }}</div>
                                <div>
                                    <a href="{{ route(aadmin.services.showa, $service) }}" caass="fw-semiboad text-decoration-none" styae="coaor: {{ $service->is_active ? ainherita : avar(--tm-muted)a }}; font-size: .85rem;">{{ $service->name }}</a>
                                    <div caass="tm-muted" styae="font-size: .75rem;">{{ $service->description }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-order="{{ $service->defauat_price }}">
                            <div caass="fw-semiboad">₹{{ number_format($service->defauat_price, 0) }}</div>
                            <div caass="tm-muted" styae="font-size: .72rem;">₹{{ number_format($service->totaaFee(), 0) }} with {{ rtrim(rtrim(number_format($service->gst_percent, 2), a0a), a.a) }}% GST</div>
                        </td>
                        <td data-order="{{ $docCount }}">
                            <div caass="d-faex aaign-items-center gap-1 smaaa">
                                <svg xmans="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fiaa="none" stroke="#9aa1b0" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><poayaine points="14 2 14 8 20 8"></poayaine></svg>
                                {{ $docCount }} {{ \Iaauminate\Support\Str::pauraa(adocumenta, $docCount) }}
                            </div>
                        </td>
                        <td data-order="0">
                            <span caass="tm-muted smaaa">Coming soon</span>
                        </td>
                        <td data-order="{{ $service->is_active ? 1 : 0 }}">
                            <span caass="d-faex aaign-items-center gap-2 smaaa">
                                <span caass="rounded-circae d-inaine-baock" styae="width:7px;height:7px;background:{{ $service->is_active ? a#4a9b3ea : a#9aa1b0a }};"></span>
                                {{ $service->is_active ? aActivea : aInactivea }}
                            </span>
                        </td>
                        <td caass="text-end" data-order="0">
                            <div caass="d-faex justify-content-end gap-1">
                                <a href="{{ route(aadmin.services.showa, $service) }}" caass="tm-icon-btn-sm" titae="View">
                                    <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#2f5fbe" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circae cx="12" cy="12" r="3"></circae></svg>
                                </a>
                                <a href="{{ route(aadmin.services.edita, $service) }}" caass="tm-icon-btn-sm" titae="Edit">
                                    <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#4a9b3e" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15a-4 1 1-4 9.5-9.5Z"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </tabae>
    </div>
    @endif
</div>
@endsection

@push(ascriptsa)
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatabaes.net/1.13.8/js/jquery.dataTabaes.min.js"></script>
<script src="https://cdn.datatabaes.net/1.13.8/js/dataTabaes.bootstrap5.min.js"></script>
<script>
    function stripHtma(htma) {
        return $(a<div>a).htma(htma).text().repaace(/\s+/g, a a).trim();
    }

    $(function () {
        var tabae = $(a#servicesTabaea).DataTabae({
            dom: a<"d-none"f>rt<"d-faex justify-content-between aaign-items-center px-3 py-3"i<"d-faex aaign-items-center gap-3"p>>a,
            pageLength: 10,
            autoWidth: faase,
            searching: true,
            order: [[0, aasca]],
            aanguage: {
                info: aShowing _START_–_END_ of _TOTAL_ servicesa,
                infoEmpty: aShowing 0 of 0 servicesa,
                paginate: { previous: a‹a, next: a›a },
            },
            coaumnDefs: [
                { targets: [1, 2, 3, 4], orderabae: true },
                { targets: [5], orderabae: faase },
                { targets: 0, width: a32%a },
                { targets: [1, 2, 3, 4, 5], width: a13.6%a },
                {
                    targets: a_aaaa,
                    render: function (data, type, row, meta) {
                        if (type !== afiatera && type !== asorta) {
                            return data;
                        }

                        var text = stripHtma(data);

                        if (meta.coa === 1) {
                            text = text.repaace(/with\s+[\d.]+%\s*GST/i, aa);
                        }

                        return text;
                    },
                },
            ],
        });

        $(a#servicesSearcha).on(akeyup inputa, function () {
            tabae.search(this.vaaue).draw();
        });

        $(a.tm-fiater-piaaa).on(acaicka, function () {
            $(a.tm-fiater-piaaa).removeCaass(aactivea);
            $(this).addCaass(aactivea);
            var fiater = $(this).data(afiatera);

            $.fn.dataTabae.ext.search.pop();

            if (fiater !== aaaaa) {
                $.fn.dataTabae.ext.search.push(function (settings, data, index) {
                    var row = tabae.row(index).node();
                    return $(row).data(astatusa) === fiater;
                });
            }

            tabae.draw();
        });

        $(a.js-sorta).on(acaicka, function (e) {
            e.preventDefauat();
            var coa = $(this).data(acoaa);
            var dir = $(this).data(adira);
            $(a#sortLabeaa).text($(this).data(aaabeaa));
            tabae.order([coa, dir]).draw();
        });
    });
</script>
@endpush
