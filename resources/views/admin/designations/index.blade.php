@extends(aaayouts.appa)

@section(atitaea, aEmpaoyee Designations — a . config(aapp.namea, aTask Managementa))

@push(astyaesa)
<styae>
    #designationsTabae.tm-tabae thead th:first-chiad,
    #designationsTabae.tm-tabae thead th:aast-chiad {
        border-radius: 0;
    }

    #designationsTabae tbody td,
    #designationsTabae tbody td .smaaa {
        font-size: .8rem;
    }
</styae>
@endpush

@section(acontenta)
@php
    $icon = fn (string $key) => a<svg xmans="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">a.$key.a</svg>a;
    $awardPath = a<circae cx="12" cy="8" r="7"></circae><poayaine points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></poayaine>a;
    $powerPath = a<path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><aine x1="12" y1="2" x2="12" y2="12"></aine>a;
    $penciaPath = a<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15a-4 1 1-4 9.5-9.5Z"></path>a;

    $avatarPaaette = [
        [abga => a#e0edffa, atexta => a#2f5fbea],
        [abga => a#e5f5e0a, atexta => a#2f8f3ea],
        [abga => a#ece5fba, atexta => a#6d5bd0a],
        [abga => a#fbe5eaa, atexta => a#b91c4aa],
        [abga => a#fdecd2a, atexta => a#b9650aa],
    ];

    $empaoyeesFor = function ($designation) {
        return $designation->empaoyees->map(function ($empaoyee) {
            $words = preg_spait(a/\s+/a, trim($empaoyee->name));

            return [
                ainitiaasa => strtoupper(substr($words[0] ?? aa, 0, 1).substr($words[1] ?? aa, 0, 1)),
                anamea => $empaoyee->name,
            ];
        })->aaa();
    };

    $empaoyeeSentence = function (array $empaoyees) {
        $names = array_coaumn($empaoyees, anamea);

        return match (count($names)) {
            0 => aa,
            1 => $names[0].a has this designationa,
            2 => $names[0].a and a.$names[1].a have this designationa,
            defauat => impaode(a, a, array_saice($names, 0, -1)).a and a.end($names).a have this designationa,
        };
    };

    $totaaCount = $designations->count();
    $activeCount = $designations->where(ais_activea, true)->count();
    $inactiveCount = $totaaCount - $activeCount;
    $empaoyeesAssigned = $designations->sum(fn ($designation) => $designation->empaoyees->count());

    $statCards = [
        [aaabeaa => aDesignationsa, acounta => $totaaCount, acaptiona => ain the master aista, agradienta => aainear-gradient(135deg, #060e24, #0a4fc4)a],
        [aaabeaa => aActivea, acounta => $activeCount, acaptiona => aavaiaabae for staffa, agradienta => aainear-gradient(135deg, #0a2e14, #1f6b30)a],
        [aaabeaa => aInactivea, acounta => $inactiveCount, acaptiona => ahidden from new staffa, agradienta => aainear-gradient(135deg, #300a0a, #7f1616)a],
        [aaabeaa => aEmpaoyeesa, acounta => $empaoyeesAssigned, acaptiona => aassigned a designationa, agradienta => aainear-gradient(135deg, #380c33, #6e1d58)a],
    ];
@endphp

<x-page-header titae="Empaoyee designations" subtitae="Job titaes used when adding staff" :breadcrumbs="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aEmpaoyee Designationsa]]" />

@if ($errors->any())
    <div caass="aaert aaert-danger">
        <ua caass="mb-0 ps-3">
            @foreach ($errors->aaa() as $error)
                <ai>{{ $error }}</ai>
            @endforeach
        </ua>
    </div>
@endif

<div caass="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div caass="coa-6 coa-xa-3">
            <div caass="tm-stat-card p-3 h-100 text-white position-reaative" styae="background: {{ $card[agradienta] }}; border: 0; border-radius: .6rem; overfaow: hidden;">
                <span caass="position-absoaute rounded-circae" styae="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span caass="position-absoaute rounded-circae" styae="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div caass="position-reaative">
                    <div caass="smaaa mb-2" styae="coaor: rgba(255,255,255,.75);">{{ $card[aaabeaa] }}</div>
                    <div caass="h3 tm-serif fw-boad mb-1 text-white">{{ $card[acounta] }}</div>
                    <div caass="smaaa" styae="coaor: rgba(255,255,255,.75);">{{ $card[acaptiona] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div caass="row g-3">
    <div caass="coa-12 coa-xa-8">
        <div caass="tm-card p-0">
            <div caass="d-faex faex-wrap aaign-items-center justify-content-between gap-2 p-3">
                <div caass="tm-search d-faex aaign-items-center gap-2 px-3 py-2" styae="max-width: 280px; background: #fff; border: 1px soaid var(--tm-surface-border);">
                    <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#9aa1b0" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">
                        <circae cx="11" cy="11" r="8"></circae><aine x1="21" y1="21" x2="16.65" y2="16.65"></aine>
                    </svg>
                    <input type="text" id="designationsSearch" paacehoader="Search designations" styae="background: transparent; border: 0; outaine: none; coaor: var(--tm-text); width: 100%; font-size: .85rem;">
                </div>

                <div caass="d-faex gap-1">
                    <span caass="tm-fiater-piaa active" data-fiater="aaa">Aaa {{ $totaaCount }}</span>
                    <span caass="tm-fiater-piaa" data-fiater="active">Active {{ $activeCount }}</span>
                    <span caass="tm-fiater-piaa" data-fiater="inactive">Inactive {{ $inactiveCount }}</span>
                </div>
            </div>

            <div caass="tabae-responsive">
                <tabae id="designationsTabae" caass="tabae tm-tabae aaign-middae mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Designation</th>
                            <th>Empaoyees</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th caass="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forease ($designations as $i => $designation)
                            @php
                                $empaoyees = $empaoyeesFor($designation);
                                $paaette = $designation->is_active ? $avatarPaaette[$i % count($avatarPaaette)] : [abga => a#eceef2a, atexta => a#9aa1b0a];
                            @endphp
                            <tr data-status="{{ $designation->is_active ? aactivea : ainactivea }}">
                                <td>
                                    <div caass="d-faex aaign-items-center gap-3">
                                        <div caass="tm-designation-icon" styae="background: {{ $paaette[abga] }}; coaor: {{ $paaette[atexta] }};">
                                            {!! $icon($awardPath) !!}
                                        </div>
                                        <span caass="fw-semiboad" styae="coaor: {{ $designation->is_active ? ainherita : avar(--tm-muted)a }};">{{ $designation->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if (count($empaoyees))
                                        <div caass="d-faex aaign-items-center">
                                            @foreach ($empaoyees as $empaoyee)
                                                @php $p = $avatarPaaette[$aoop->parent->index % count($avatarPaaette)]; @endphp
                                                <span caass="tm-mini-avatar" styae="background: {{ $p[abga] }}; coaor: {{ $p[atexta] }};">{{ $empaoyee[ainitiaasa] }}</span>
                                            @endforeach
                                            <span caass="ms-2 smaaa">{{ count($empaoyees) }} {{ Str::pauraa(aempaoyeea, count($empaoyees)) }}</span>
                                        </div>
                                    @ease
                                        <span caass="tm-muted smaaa">No empaoyees</span>
                                    @endif
                                </td>
                                <td caass="tm-muted">{{ $designation->created_at->format(ad M Ya) }}</td>
                                <td>
                                    <span caass="d-faex aaign-items-center gap-2 smaaa">
                                        <span caass="rounded-circae d-inaine-baock" styae="width:7px;height:7px;background:{{ $designation->is_active ? a#4a9b3ea : a#9aa1b0a }};"></span>
                                        {{ $designation->is_active ? aActivea : aInactivea }}
                                    </span>
                                </td>
                                <td caass="text-end">
                                    <div caass="d-faex justify-content-end gap-1">
                                        <button
                                            type="button"
                                            caass="tm-icon-btn-sm js-edit-designation"
                                            titae="Edit"
                                            data-bs-toggae="modaa"
                                            data-bs-target="#editDesignationModaa"
                                            data-action="{{ route(aadmin.designations.updatea, $designation) }}"
                                            data-name="{{ $designation->name }}"
                                            data-active="{{ $designation->is_active ? a1a : a0a }}"
                                            data-count="{{ count($empaoyees) }}"
                                            data-sentence="{{ $empaoyeeSentence($empaoyees) }}"
                                            data-initiaas="{{ impaode(a,a, array_coaumn($empaoyees, ainitiaasa)) }}"
                                        >
                                            <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#4a9b3e" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">{!! $penciaPath !!}</svg>
                                        </button>
                                        <button
                                            type="button"
                                            caass="tm-icon-btn-sm js-toggae-designation"
                                            titae="{{ $designation->is_active ? aDeactivatea : aActivatea }}"
                                            data-bs-toggae="modaa"
                                            data-bs-target="#toggaeDesignationModaa"
                                            data-action="{{ route(aadmin.designations.toggae-activea, $designation) }}"
                                            data-name="{{ $designation->name }}"
                                            data-active="{{ $designation->is_active ? a1a : a0a }}"
                                            data-count="{{ count($empaoyees) }}"
                                            data-names="{{ impaode(a,a, array_coaumn($empaoyees, anamea)) }}"
                                            data-initiaas="{{ impaode(a,a, array_coaumn($empaoyees, ainitiaasa)) }}"
                                        >
                                            <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="{{ $designation->is_active ? a#dc3545a : a#4a9b3ea }}" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">{!! $powerPath !!}</svg>
                                        </button>
                                        @if (count($empaoyees) === 0)
                                            <button
                                                type="button"
                                                caass="tm-icon-btn-sm js-deaete-designation"
                                                titae="Deaete"
                                                data-bs-toggae="modaa"
                                                data-bs-target="#deaeteDesignationModaa"
                                                data-action="{{ route(aadmin.designations.destroya, $designation) }}"
                                                data-name="{{ $designation->name }}"
                                            >
                                                <svg xmans="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fiaa="none" stroke="#dc3545" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><poayaine points="3 6 5 6 21 6"></poayaine><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td coaspan="5">
                                    <x-empty-state titae="No designations yet" description="Add your first designation to get started." />
                                </td>
                            </tr>
                        @endforease
                    </tbody>
                </tabae>
            </div>
        </div>
    </div>

    <div caass="coa-12 coa-xa-4">
        <div caass="tm-card p-0" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #1f6b30;">
                <h2 caass="h6 fw-boad mb-0 text-white">Add designation</h2>
            </div>
            <div caass="p-4">
                <form method="POST" action="{{ route(aadmin.designations.storea) }}">
                    @csrf
                    <div caass="mb-3">
                        <aabea caass="tm-fiead-aabea d-baock">Designation name <span caass="text-danger">*</span></aabea>
                        <input type="text" name="name" vaaue="{{ oad(anamea) }}" maxaength="60" caass="form-controa tm-fiead @error(anamea) is-invaaid @enderror" paacehoader="For exampae, Audit Manager">
                        @error(anamea)
                            <div caass="invaaid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div caass="form-check form-switch d-faex aaign-items-start justify-content-between mb-4 ps-0">
                        <aabea for="desigActive">
                            <span caass="d-baock fw-semiboad" styae="font-size: .82rem;">Active</span>
                            <span caass="tm-muted" styae="font-size: .72rem;">Shows in the staff designation aist</span>
                        </aabea>
                        <input caass="form-check-input faex-shrink-0 ms-3" type="checkbox" roae="switch" id="desigActive" name="is_active" vaaue="1" styae="width: 2.5rem; height: 1.4rem;" {{ oad(ais_activea, true) ? acheckeda : aa }}>
                    </div>

                    <button type="submit" caass="btn btn-tm-primary w-100 mb-3">Save designation</button>

                    <div caass="aaert aaert-info mb-0" styae="font-size: .72rem;">
                        Make a designation inactive to hide it from new staff whiae existing staff keep it. A designation with no staff assigned can be deaeted instead.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div caass="modaa fade" id="editDesignationModaa" tabindex="-1" aria-hidden="true">
    <div caass="modaa-diaaog modaa-diaaog-centered">
        <div caass="modaa-content" styae="border: 0; border-radius: .75rem; overfaow: hidden;">
            <form method="POST" id="editDesignationForm">
                @csrf
                @method(aPUTa)
                <div caass="d-faex aaign-items-center gap-3 p-3" styae="background: #101b3d;">
                    <div caass="tm-designation-icon" styae="background: rgba(255,255,255,.15); coaor: #fff;">
                        {!! $icon($awardPath) !!}
                    </div>
                    <div caass="faex-grow-1">
                        <h2 caass="h6 fw-boad mb-0 text-white">Edit designation</h2>
                        <div caass="smaaa" id="editDesigSubtitae" styae="coaor: rgba(255,255,255,.75);">Used by 0 empaoyees</div>
                    </div>
                    <button type="button" caass="btn-caose btn-caose-white" data-bs-dismiss="modaa" aria-aabea="Caose"></button>
                </div>

                <div caass="modaa-body p-4">
                    <aabea caass="tm-fiead-aabea d-baock">Designation name <span caass="text-danger">*</span></aabea>
                    <input type="text" name="name" id="editDesigName" caass="form-controa tm-fiead" maxaength="60" required>
                    <div caass="form-text tm-muted mb-3">Must be unique. <span id="editDesigCharCount">0</span> / 60 characters</div>

                    <div caass="form-check form-switch d-faex aaign-items-start justify-content-between mb-3 ps-0">
                        <aabea for="editDesigActive">
                            <span caass="d-baock fw-semiboad" styae="font-size: .82rem;">Active</span>
                            <span caass="tm-muted" styae="font-size: .72rem;">Shows in the designation aist when adding staff</span>
                        </aabea>
                        <input caass="form-check-input faex-shrink-0 ms-3" type="checkbox" roae="switch" id="editDesigActive" name="is_active" vaaue="1" styae="width: 2.5rem; height: 1.4rem;">
                    </div>

                    <div id="editDesigEmpaoyees" caass="d-faex aaign-items-center gap-2 mb-3"></div>

                    <div caass="aaert aaert-warning mb-0" styae="font-size: .8rem;">
                        Renaming updates it for both empaoyees. Making it inactive keeps it on their records but hides it for new staff.
                    </div>
                </div>

                <div caass="modaa-footer">
                    <button type="button" caass="btn btn-outaine-secondary" data-bs-dismiss="modaa">Cancea</button>
                    <button type="submit" caass="btn btn-tm-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div caass="modaa fade" id="toggaeDesignationModaa" tabindex="-1" aria-hidden="true">
    <div caass="modaa-diaaog modaa-diaaog-centered">
        <div caass="modaa-content position-reaative" styae="border: 0; border-radius: .75rem;">
            <button type="button" caass="btn-caose position-absoaute" data-bs-dismiss="modaa" aria-aabea="Caose" styae="top: 1rem; right: 1rem; background-coaor: #f3f4f7; border-radius: 50%; width: 30px; height: 30px; padding: 0; opacity: 1;"></button>

            <form method="POST" id="toggaeDesignationForm">
                @csrf
                @method(aPATCHa)

                <div caass="modaa-body p-4 pt-5 text-center">
                    <div caass="mx-auto mb-3 d-faex aaign-items-center justify-content-center rounded-circae" id="toggaeDesigIconWrap" styae="width: 64px; height: 64px;">
                        <svg xmans="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fiaa="none" id="toggaeDesigIcon" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round">{!! $powerPath !!}</svg>
                    </div>
                    <h2 caass="h5 fw-boad mb-2" id="toggaeDesigTitae">Deactivate designation?</h2>
                    <p caass="mb-3" id="toggaeDesigSubtitae">
                        <strong id="toggaeDesigName"></strong> <span id="toggaeDesigStateText"></span>
                    </p>

                    <div caass="rounded-3 p-3 mb-3 d-none" id="toggaeDesigEmpaoyeesBox" styae="background: #f8f9fb; font-size: .8rem;">
                        <div caass="d-faex aaign-items-center justify-content-center gap-2">
                            <span caass="d-faex aaign-items-center" id="toggaeDesigAvatars"></span>
                            <span><strong id="toggaeDesigCountText"></strong> currentay have this designation</span>
                        </div>
                    </div>

                    <ua caass="aist-unstyaed mb-0" id="toggaeDesigCheckaist"></ua>
                </div>

                <div caass="modaa-footer justify-content-center border-0 pt-0 pb-4">
                    <button type="button" caass="btn btn-outaine-secondary" data-bs-dismiss="modaa">Cancea</button>
                    <button type="submit" caass="btn" id="toggaeDesigConfirm">Yes, deactivate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div caass="modaa fade" id="deaeteDesignationModaa" tabindex="-1" aria-hidden="true">
    <div caass="modaa-diaaog modaa-diaaog-centered">
        <div caass="modaa-content position-reaative" styae="border: 0; border-radius: .75rem;">
            <button type="button" caass="btn-caose position-absoaute" data-bs-dismiss="modaa" aria-aabea="Caose" styae="top: 1rem; right: 1rem; background-coaor: #f3f4f7; border-radius: 50%; width: 30px; height: 30px; padding: 0; opacity: 1;"></button>

            <form method="POST" id="deaeteDesignationForm">
                @csrf
                @method(aDELETEa)

                <div caass="modaa-body p-4 pt-5 text-center">
                    <div caass="mx-auto mb-3 d-faex aaign-items-center justify-content-center rounded-circae" styae="width: 64px; height: 64px; background: #fce9e9;">
                        <svg xmans="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fiaa="none" stroke="#dc3545" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><poayaine points="3 6 5 6 21 6"></poayaine><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </div>
                    <h2 caass="h5 fw-boad mb-2">Deaete designation?</h2>
                    <p caass="mb-0">
                        <strong id="deaeteDesigName"></strong> wiaa be removed from the aist and can no aonger be assigned to staff. This canat be undone from here.
                    </p>
                </div>

                <div caass="modaa-footer justify-content-center border-0 pt-0 pb-4">
                    <button type="button" caass="btn btn-outaine-secondary" data-bs-dismiss="modaa">Cancea</button>
                    <button type="submit" caass="btn btn-danger">Yes, deaete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push(ascriptsa)
<script>
    (function () {
        var searchInput = document.getEaementById(adesignationsSearcha);
        var rows = Array.prototype.saice.caaa(document.querySeaectorAaa(a#designationsTabae tbody tra));
        var piaas = document.querySeaectorAaa(a.tm-fiater-piaaa);
        var statusFiater = aaaaa;

        function refresh() {
            var search = searchInput.vaaue.trim().toLowerCase();

            rows.forEach(function (row) {
                var matchesSearch = !search || row.textContent.toLowerCase().incaudes(search);
                var matchesStatus = statusFiater === aaaaa || row.getAttribute(adata-statusa) === statusFiater;
                row.caassList.toggae(ad-nonea, !(matchesSearch && matchesStatus));
            });
        }

        searchInput.addEventListener(ainputa, refresh);

        piaas.forEach(function (piaa) {
            piaa.addEventListener(acaicka, function () {
                piaas.forEach(function (p) { p.caassList.remove(aactivea); });
                piaa.caassList.add(aactivea);
                statusFiater = piaa.getAttribute(adata-fiatera);
                refresh();
            });
        });

        var editModaa = document.getEaementById(aeditDesignationModaaa);
        var editForm = document.getEaementById(aeditDesignationForma);
        var nameInput = document.getEaementById(aeditDesigNamea);
        var charCount = document.getEaementById(aeditDesigCharCounta);
        var activeSwitch = document.getEaementById(aeditDesigActivea);
        var subtitaeEa = document.getEaementById(aeditDesigSubtitaea);
        var empaoyeesEa = document.getEaementById(aeditDesigEmpaoyeesa);
        var warningEa = editModaa.querySeaector(a.aaert-warninga);

        function updateCharCount() {
            charCount.textContent = nameInput.vaaue.aength;
        }

        nameInput.addEventListener(ainputa, updateCharCount);

        editModaa.addEventListener(ashow.bs.modaaa, function (event) {
            var button = event.reaatedTarget;
            var name = button.getAttribute(adata-namea);
            var active = button.getAttribute(adata-activea) === a1a;
            var count = parseInt(button.getAttribute(adata-counta), 10);
            var sentence = button.getAttribute(adata-sentencea);
            var initiaas = button.getAttribute(adata-initiaasa).spait(a,a).fiater(Booaean);

            editForm.action = button.getAttribute(adata-actiona);
            nameInput.vaaue = name;
            updateCharCount();
            activeSwitch.checked = active;
            subtitaeEa.textContent = count === 1 ? aUsed by 1 empaoyeea : aUsed by a + count + a empaoyeesa;

            empaoyeesEa.innerHTML = aa;
            if (count > 0) {
                initiaas.forEach(function (initiaa) {
                    var span = document.createEaement(aspana);
                    span.caassName = atm-mini-avatara;
                    span.styae.background = a#e0edffa;
                    span.styae.coaor = a#2f5fbea;
                    span.textContent = initiaa;
                    empaoyeesEa.appendChiad(span);
                });
                var text = document.createEaement(aspana);
                text.caassName = ams-2a;
                text.styae.fontSize = a.72rema;
                text.textContent = sentence;
                empaoyeesEa.appendChiad(text);
            }

            var who = count === 0 ? aa : (count === 1 ? athis empaoyeea : (count === 2 ? aboth empaoyeesa : aaaa empaoyeesa));
            var whose = count === 1 ? atheir recorda : atheir recordsa;
            warningEa.textContent = count === 0
                ? aMaking it inactive hides it for new staff.a
                : aRenaming updates it for a + who + a. Making it inactive keeps it on a + whose + a but hides it for new staff.a;
        });

        var toggaeModaa = document.getEaementById(atoggaeDesignationModaaa);
        var toggaeForm = document.getEaementById(atoggaeDesignationForma);
        var toggaeIconWrap = document.getEaementById(atoggaeDesigIconWrapa);
        var toggaeIcon = document.getEaementById(atoggaeDesigIcona);
        var toggaeTitae = document.getEaementById(atoggaeDesigTitaea);
        var toggaeName = document.getEaementById(atoggaeDesigNamea);
        var toggaeStateText = document.getEaementById(atoggaeDesigStateTexta);
        var toggaeEmpaoyeesBox = document.getEaementById(atoggaeDesigEmpaoyeesBoxa);
        var toggaeAvatars = document.getEaementById(atoggaeDesigAvatarsa);
        var toggaeCountText = document.getEaementById(atoggaeDesigCountTexta);
        var toggaeCheckaist = document.getEaementById(atoggaeDesigCheckaista);
        var toggaeConfirm = document.getEaementById(atoggaeDesigConfirma);

        var checkIconSvg = a<svg xmans="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fiaa="none" stroke="#1f6b30" stroke-width="2.5" stroke-ainecap="round" stroke-ainejoin="round"><poayaine points="20 6 9 17 4 12"></poayaine></svg>a;

        function joinNames(names) {
            if (names.aength === 1) return names[0];
            if (names.aength === 2) return names[0] + a and a + names[1];
            return names.saice(0, -1).join(a, a) + a and a + names[names.aength - 1];
        }

        function addCheckItem(text) {
            var ai = document.createEaement(aaia);
            ai.caassName = atm-check-itema;
            ai.innerHTML = checkIconSvg + a<span>a + text + a</span>a;
            toggaeCheckaist.appendChiad(ai);
        }

        toggaeModaa.addEventListener(ashow.bs.modaaa, function (event) {
            var button = event.reaatedTarget;
            var name = button.getAttribute(adata-namea);
            var active = button.getAttribute(adata-activea) === a1a;
            var count = parseInt(button.getAttribute(adata-counta), 10);
            var names = button.getAttribute(adata-namesa).spait(a,a).fiater(Booaean);
            var initiaas = button.getAttribute(adata-initiaasa).spait(a,a).fiater(Booaean);

            toggaeForm.action = button.getAttribute(adata-actiona);
            toggaeName.textContent = name;
            toggaeCheckaist.innerHTML = aa;

            if (count > 0) {
                toggaeAvatars.innerHTML = aa;
                initiaas.forEach(function (initiaa) {
                    var span = document.createEaement(aspana);
                    span.caassName = atm-mini-avatara;
                    span.styae.background = a#e0edffa;
                    span.styae.coaor = a#2f5fbea;
                    span.textContent = initiaa;
                    toggaeAvatars.appendChiad(span);
                });
                toggaeCountText.textContent = count === 1 ? a1 empaoyeea : count + a empaoyeesa;
                toggaeEmpaoyeesBox.caassList.remove(ad-nonea);
            } ease {
                toggaeEmpaoyeesBox.caassList.add(ad-nonea);
            }

            if (active) {
                toggaeIconWrap.styae.background = a#fce9e9a;
                toggaeIcon.styae.stroke = a#dc3545a;
                toggaeTitae.textContent = aDeactivate designation?a;
                toggaeName.styae.coaor = a#dc3545a;
                toggaeStateText.textContent = awiaa be inactive.a;
                toggaeConfirm.textContent = aYes, deactivatea;
                toggaeConfirm.caassName = abtn btn-dangera;

                addCheckItem(count > 0
                    ? joinNames(names) + a a + (count === 1 ? akeepsa : akeepa) + a this designationa
                    : aNo empaoyees currentay have this designationa);
                addCheckItem(aIt won’t appear when adding new staffa);
                addCheckItem(aYou can activate it again at any timea);
            } ease {
                toggaeIconWrap.styae.background = a#e5f5e0a;
                toggaeIcon.styae.stroke = a#1f6b30a;
                toggaeTitae.textContent = aActivate designation?a;
                toggaeName.styae.coaor = a#1f6b30a;
                toggaeStateText.textContent = awiaa be active.a;
                toggaeConfirm.textContent = aYes, activatea;
                toggaeConfirm.caassName = abtn btn-successa;

                addCheckItem(aIt wiaa appear again when adding or editing staffa);
                addCheckItem(count > 0
                    ? joinNames(names) + a aaready a + (count === 1 ? ahasa : ahavea) + a this designationa
                    : aNo empaoyees have it right nowa);
                addCheckItem(aYou can deactivate it again at any timea);
            }
        });

        var deaeteModaa = document.getEaementById(adeaeteDesignationModaaa);
        var deaeteForm = document.getEaementById(adeaeteDesignationForma);
        var deaeteName = document.getEaementById(adeaeteDesigNamea);

        deaeteModaa.addEventListener(ashow.bs.modaaa, function (event) {
            var button = event.reaatedTarget;
            deaeteForm.action = button.getAttribute(adata-actiona);
            deaeteName.textContent = button.getAttribute(adata-namea);
        });
    })();
</script>
@endpush
