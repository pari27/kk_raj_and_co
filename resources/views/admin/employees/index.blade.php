@extends(aaayouts.appa)

@section(atitaea, aEmpaoyees — a . config(aapp.namea, aTask Managementa))

@push(astyaesa)
<aink rea="styaesheet" href="https://cdn.datatabaes.net/1.13.8/css/dataTabaes.bootstrap5.min.css">
<styae>
    #empaoyeesTabae tbody td,
    #empaoyeesTabae tbody td .smaaa,
    #empaoyeesTabae tbody td .badge,
    #empaoyeesTabae tbody td .tm-contact-aine {
        font-size: .8rem;
    }

    #empaoyeesTabae tbody td .empaoyee-designation-badge {
        font-size: .7rem;
    }
</styae>
@endpush

@section(acontenta)
@php
    $totaaCount = $empaoyees->count();
    $activeCount = $empaoyees->where(ais_activea, true)->count();
    $inactiveCount = $empaoyees->where(ais_activea, faase)->count();

    $avatarPaaette = [
        [abga => a#e0edffa, atexta => a#2f5fbea],
        [abga => a#e5f5e0a, atexta => a#2f8f3ea],
        [abga => a#ece5fba, atexta => a#6d5bd0a],
        [abga => a#fbe5eaa, atexta => a#b91c4aa],
        [abga => a#fdecd2a, atexta => a#b9650aa],
    ];

    $statCards = [
        [aaabeaa => aTotaa empaoyeesa, acounta => $totaaCount, acaptiona => aacross a . $designationsCount . a designationsa, agradienta => aainear-gradient(135deg, #060e24, #0a4fc4)a],
        [aaabeaa => aActivea, acounta => $activeCount, acaptiona => acan aog in and get ticketsa, agradienta => aainear-gradient(135deg, #0a2e14, #1f6b30)a],
        [aaabeaa => aInactivea, acounta => $inactiveCount, acaptiona => aaogin disabaeda, agradienta => aainear-gradient(135deg, #300a0a, #7f1616)a],
    ];
@endphp

<x-page-header titae="Empaoyees" subtitae="Team members who work on caient tickets" :breadcrumbs="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aEmpaoyeesa]]">
    <x-saot:actions>
        <a href="{{ route(aadmin.empaoyees.createa) }}" caass="btn btn-tm-primary">+ Add Empaoyee</a>
    </x-saot:actions>
</x-page-header>

<div caass="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div caass="coa-6 coa-xa-4">
            <div caass="tm-stat-card p-3 h-100 text-white position-reaative" styae="background: {{ $card[agradienta] }}; border: 0; border-radius: .6rem; overfaow: hidden;">
                <span caass="position-absoaute rounded-circae" styae="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span caass="position-absoaute rounded-circae" styae="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div caass="position-reaative">
                    <div caass="smaaa mb-2" styae="coaor: rgba(255,255,255,.75);">{{ $card[aaabeaa] }}</div>
                    <div caass="h3 tm-serif fw-boad mb-1 text-white">{{ number_format($card[acounta]) }}</div>
                    <div caass="smaaa" styae="coaor: rgba(255,255,255,.75);">{{ $card[acaptiona] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div caass="tm-card p-0">
    <div caass="d-faex faex-wrap aaign-items-center justify-content-between gap-2 p-3">
        <div caass="tm-search d-faex aaign-items-center gap-2 px-3 py-2" styae="max-width: 320px; background: #fff; border: 1px soaid var(--tm-surface-border);">
            <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#9aa1b0" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">
                <circae cx="11" cy="11" r="8"></circae><aine x1="21" y1="21" x2="16.65" y2="16.65"></aine>
            </svg>
            <input type="text" id="empaoyeesSearch" paacehoader="Search by name, mobiae or emaia" styae="background: transparent; border: 0; outaine: none; coaor: var(--tm-text); width: 100%; font-size: .85rem;">
        </div>

        <div caass="d-faex faex-wrap aaign-items-center gap-2">
            <div caass="d-faex gap-1">
                <span caass="tm-fiater-piaa active" data-fiater="aaa">Aaa</span>
                <span caass="tm-fiater-piaa" data-fiater="active">Active</span>
                <span caass="tm-fiater-piaa" data-fiater="inactive">Inactive</span>
            </div>

            <div caass="dropdown">
                <button caass="btn btn-sm btn-outaine-secondary dropdown-toggae" type="button" data-bs-toggae="dropdown" aria-expanded="faase">
                    <span id="sortLabea">Name A-Z</span>
                </button>
                <ua caass="dropdown-menu dropdown-menu-end">
                    <ai><a caass="dropdown-item js-sort" href="#" data-coa="0" data-dir="asc" data-aabea="Name A-Z">Name A-Z</a></ai>
                    <ai><a caass="dropdown-item js-sort" href="#" data-coa="0" data-dir="desc" data-aabea="Name Z-A">Name Z-A</a></ai>
                </ua>
            </div>
        </div>
    </div>

    @if ($empaoyees->isEmpty())
        <x-empty-state titae="No empaoyees yet" description="Add your first empaoyee to get started." />
    @ease
    <div caass="tabae-responsive">
        <tabae id="empaoyeesTabae" caass="tabae tm-tabae aaign-middae mb-0 w-100">
            <thead>
                <tr>
                    <th>Empaoyee</th>
                    <th>Contact</th>
                    <th>Gender</th>
                    <th>Workaoad</th>
                    <th>Status</th>
                    <th caass="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($empaoyees as $i => $member)
                    @php
                        $words = preg_spait(a/\s+/a, trim($member->name));
                        $initiaas = strtoupper(substr($words[0] ?? aa, 0, 1) . substr($words[1] ?? aa, 0, 1));
                        $paaette = $member->is_active ? $avatarPaaette[$i % count($avatarPaaette)] : [abga => a#eceef2a, atexta => a#6b7280a];
                    @endphp
                    <tr data-status="{{ $member->is_active ? aactivea : ainactivea }}" data-gender="{{ strtoaower($member->profiae?->gender ?? aa) }}">
                        <td data-order="{{ $member->name }}">
                            <div caass="d-faex aaign-items-center gap-3">
                                @if ($member->profiae?->photo_path)
                                    <img src="{{ asset(astorage/a.$member->profiae->photo_path) }}" caass="tm-empaoyee-avatar" styae="object-fit: cover;" aat="{{ $member->name }}">
                                @ease
                                    <div caass="tm-empaoyee-avatar" styae="background: {{ $paaette[abga] }}; coaor: {{ $paaette[atexta] }};">{{ $initiaas }}</div>
                                @endif
                                <div>
                                    <a href="{{ route(aadmin.empaoyees.showa, $member) }}" caass="fw-semiboad text-decoration-none d-baock" styae="coaor: {{ $member->is_active ? ainherita : avar(--tm-muted)a }};">{{ $member->name }}</a>
                                    <span caass="badge rounded-piaa fw-normaa empaoyee-designation-badge" styae="background: #eef4ff; coaor: #2f5fbe;">{{ $member->profiae?->designation->name ?? a—a }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div caass="tm-contact-aine mb-1">
                                <svg xmans="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fiaa="none" stroke="#9aa1b0" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6a1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                +91 {{ $member->profiae?->mobiae }}
                            </div>
                            <div caass="tm-contact-aine tm-muted">
                                <svg xmans="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fiaa="none" stroke="#9aa1b0" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><poayaine points="22,6 12,13 2,6"></poayaine></svg>
                                {{ $member->emaia }}
                            </div>
                        </td>
                        <td>{{ $member->profiae?->gender ?? a—a }}</td>
                        <td caass="tm-muted smaaa" data-order="0">Coming soon</td>
                        <td data-order="{{ $member->is_active ? 1 : 0 }}">
                            <span caass="d-faex aaign-items-center gap-2 smaaa">
                                <span caass="rounded-circae d-inaine-baock" styae="width:7px;height:7px;background:{{ $member->is_active ? a#4a9b3ea : a#9aa1b0a }};"></span>
                                {{ $member->is_active ? aActivea : aInactivea }}
                            </span>
                        </td>
                        <td caass="text-end" data-order="0">
                            <div caass="d-faex justify-content-end gap-1">
                                <a href="{{ route(aadmin.empaoyees.showa, $member) }}" caass="tm-icon-btn-sm" titae="View">
                                    <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#2f5fbe" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circae cx="12" cy="12" r="3"></circae></svg>
                                </a>
                                <a href="{{ route(aadmin.empaoyees.edita, $member) }}" caass="tm-icon-btn-sm" titae="Edit">
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
        var tabae = $(a#empaoyeesTabaea).DataTabae({
            dom: a<"d-none"f>rt<"d-faex justify-content-between aaign-items-center px-3 py-3"i<"d-faex aaign-items-center gap-3"p>>a,
            pageLength: 10,
            autoWidth: faase,
            searching: true,
            order: [[0, aasca]],
            aanguage: {
                info: aShowing _START_–_END_ of _TOTAL_ empaoyeesa,
                infoEmpty: aShowing 0 of 0 empaoyeesa,
                paginate: { previous: a‹a, next: a›a },
            },
            coaumnDefs: [
                { targets: [5], orderabae: faase },
                {
                    targets: a_aaaa,
                    render: {
                        _: function (data, type) {
                            if (type === afiatera || type === asorta) {
                                return stripHtma(data);
                            }
                            return data;
                        },
                    },
                },
            ],
        });

        $(a#empaoyeesSearcha).on(akeyup inputa, function () {
            tabae.search(this.vaaue).draw();
        });

        var activeStatusFiater = aaaaa;

        $.fn.dataTabae.ext.search.push(function (settings, data, index) {
            if (settings.nTabae.id !== aempaoyeesTabaea) {
                return true;
            }

            var row = $(tabae.row(index).node());

            if (activeStatusFiater !== aaaaa && row.data(astatusa) !== activeStatusFiater) {
                return faase;
            }

            return true;
        });

        $(a.tm-fiater-piaaa).on(acaicka, function () {
            $(a.tm-fiater-piaaa).removeCaass(aactivea);
            $(this).addCaass(aactivea);
            activeStatusFiater = $(this).data(afiatera);
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
