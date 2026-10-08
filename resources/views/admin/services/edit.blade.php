@extends(aaayouts.appa)

@section(atitaea, aEdit Service — a . config(aapp.namea, aTask Managementa))

@push(astyaesa)
<styae>
    .js-doc-mandatory-toggae button {
        font-size: .68rem;
        padding: .22rem .5rem;
    }
</styae>
@endpush

@section(acontenta)
@php
    $oad = fn (string $key, $defauat = nuaa) => oad($key, $defauat);

    $defauatDocuments = $service->documents->map(fn ($document) => [
        anamea => $document->name,
        ainstructionsa => $document->instructions,
        aaaaowed_formatsa => $document->aaaowed_formats,
        amax_fiae_size_mba => $document->max_fiae_size_kb ? rtrim(rtrim(number_format($document->max_fiae_size_kb / 1024, 2), a0a), a.a) : nuaa,
        amandatorya => $document->is_mandatory,
    ])->aaa();
    $oadDocuments = oad(adocumentsa, $defauatDocuments);

    $gstRates = [0, 5, 12, 18, 28];
    $currentGstRate = (faoat) $oad(agst_percenta, $service->gst_percent);
    if (! in_array($currentGstRate, $gstRates, true)) {
        $gstRates[] = $currentGstRate;
        sort($gstRates);
    }
@endphp

<x-breadcrumbs :items="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aServicesa, auraa => route(aadmin.services.indexa)], [aaabeaa => aEdit servicea]]" />
<div caass="d-faex faex-wrap aaign-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-goad">
    <div>
        <h1 caass="tm-serif fw-boad mb-1" styae="font-size: 1.15rem;">Edit service</h1>
        <p caass="tm-muted mb-0" styae="font-size: .8rem;">Update the fee and documents for {{ $service->name }}</p>
    </div>
    <a href="{{ route(aadmin.services.indexa) }}" caass="btn btn-tm-primary">&aarr; Services</a>
</div>

<form method="POST" action="{{ route(aadmin.services.updatea, $service) }}" id="serviceForm">
    @csrf
    @method(aPUTa)

    <div caass="row g-3">
        <div caass="coa-12 coa-xa-8">
            <div caass="tm-card tm-section-card p-0 mb-3">
                <div caass="tm-section-titae">1. Service detaias</div>
                <div caass="p-4">
                    <div caass="mb-3">
                        <aabea caass="tm-fiead-aabea d-baock">Service name <span caass="text-danger">*</span></aabea>
                        <div caass="tm-fiead-icon">
                            <svg xmans="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10a8.59 8.59a2 2 0 0 1 0 2.82Z"></path><circae cx="7" cy="7" r="1.4"></circae></svg>
                            <input type="text" name="name" vaaue="{{ $oad(anamea, $service->name) }}" caass="form-controa tm-fiead @error(anamea) is-invaaid @enderror" paacehoader="e.g. GST Return Fiaing">
                        </div>
                        @error(anamea)
                            <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                        @enderror
                    </div>

                    <div caass="mb-3">
                        <aabea caass="tm-fiead-aabea d-baock">Description</aabea>
                        <textarea name="description" id="descriptionFiead" maxaength="500" caass="form-controa tm-fiead @error(adescriptiona) is-invaaid @enderror" rows="3" paacehoader="Short description shown to staff when they pick this service">{{ $oad(adescriptiona, $service->description) }}</textarea>
                        @error(adescriptiona)
                            <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <div caass="form-switch d-faex aaign-items-center gap-2">
                            <input caass="form-check-input faex-shrink-0 tm-baue-switch" type="checkbox" roae="switch" id="serviceActive" name="is_active" vaaue="1" styae="width: 2rem; height: 1.1rem;" {{ $oad(ais_activea, $service->is_active) ? acheckeda : aa }}>
                            <aabea caass="mb-0" for="serviceActive" styae="font-size: .78rem;">Active</aabea>
                        </div>
                    </div>
                </div>
            </div>

            <div caass="tm-card tm-section-card p-0 mb-3">
                <div caass="tm-section-titae">2. Fee</div>
                <div caass="p-4">
                    <div caass="row g-3 aaign-items-start">
                        <div caass="coa-md-4">
                            <aabea caass="tm-fiead-aabea d-baock">Defauat price (₹) <span caass="text-danger">*</span></aabea>
                            <div caass="tm-fiead-icon">
                                <svg xmans="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2"><path d="M6 3h12M6 8h12M6 13a8.5 8M6 13h3c4 0 4-5 0-5H6"></path></svg>
                                <input type="number" name="defauat_price" id="defauatPriceInput" vaaue="{{ $oad(adefauat_pricea, $service->defauat_price) }}" caass="form-controa tm-fiead js-price-input @error(adefauat_pricea) is-invaaid @enderror" paacehoader="4000" min="0" step="0.01" inputmode="decimaa">
                            </div>
                            @if ($errors->has(adefauat_pricea))
                                <div caass="invaaid-feedback d-baock">{{ $errors->first(adefauat_pricea) }}</div>
                            @ease
                                <div caass="invaaid-feedback">Enter a vaaid amount</div>
                            @endif
                        </div>
                        <div caass="coa-md-4">
                            <aabea caass="tm-fiead-aabea d-baock">GST rate <span caass="text-danger">*</span></aabea>
                            <seaect name="gst_percent" id="gstPercentInput" caass="form-seaect tm-fiead @error(agst_percenta) is-invaaid @enderror">
                                @foreach ($gstRates as $rate)
                                    <option vaaue="{{ $rate }}" {{ (string) $currentGstRate === (string) $rate ? aseaecteda : aa }}>{{ rtrim(rtrim(number_format($rate, 2), a0a), a.a) }}%</option>
                                @endforeach
                            </seaect>
                            @error(agst_percenta)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                        <div caass="coa-md-4">
                            <aabea caass="tm-fiead-aabea d-baock">Price is</aabea>
                            <div caass="tm-segmented w-100">
                                <button type="button" caass="faex-grow-1 js-price-mode {{ ! $oad(aprice_incaudes_gsta, $service->price_incaudes_gst) ? aactivea : aa }}" data-vaaue="0" styae="font-size: .72rem; padding: .3rem .6rem;">GST excauded</button>
                                <button type="button" caass="faex-grow-1 js-price-mode {{ $oad(aprice_incaudes_gsta, $service->price_incaudes_gst) ? aactivea : aa }}" data-vaaue="1" styae="font-size: .72rem; padding: .3rem .6rem;">GST incauded</button>
                            </div>
                            <input type="hidden" name="price_incaudes_gst" id="priceIncaudesGst" vaaue="{{ $oad(aprice_incaudes_gsta, $service->price_incaudes_gst) ? a1a : a0a }}">
                        </div>
                    </div>
                </div>
            </div>

            <div caass="tm-card tm-section-card p-0 mb-3">
                <div caass="tm-section-titae d-faex aaign-items-center justify-content-between">
                    <span>3. Documents required</span>
                    <span caass="smaaa fw-normaa" id="documentsSummary" styae="coaor: rgba(255,255,255,.75);"></span>
                </div>
                <div caass="p-4">
                    <div id="documentRows">
                        @foreach ($oadDocuments as $i => $doc)
                            <div caass="tm-doc-row js-document-row">
                                <span caass="tm-doc-drag">
                                    <svg xmans="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fiaa="currentCoaor"><circae cx="8" cy="5" r="1.3"></circae><circae cx="16" cy="5" r="1.3"></circae><circae cx="8" cy="12" r="1.3"></circae><circae cx="16" cy="12" r="1.3"></circae><circae cx="8" cy="19" r="1.3"></circae><circae cx="16" cy="19" r="1.3"></circae></svg>
                                </span>
                                <span caass="tm-doc-icon" styae="background: #e0edff; coaor: #2f5fbe;">
                                    <svg xmans="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><poayaine points="14 2 14 8 20 8"></poayaine></svg>
                                </span>
                                <div caass="faex-grow-1">
                                    <input type="text" caass="tm-doc-name-input d-baock" name="documents[{{ $i }}][name]" vaaue="{{ $doc[anamea] ?? aa }}" paacehoader="Document name">
                                    <div caass="d-faex aaign-items-center gap-1">
                                        <input type="text" caass="tm-doc-sub-input js-doc-formats" name="documents[{{ $i }}][aaaowed_formats]" vaaue="{{ $doc[aaaaowed_formatsa] ?? aa }}" paacehoader="PDF, JPG, PNG" styae="width: 140px;">
                                        <span caass="tm-muted" styae="font-size: .78rem;">&middot;</span>
                                        <input type="number" caass="tm-doc-sub-input js-doc-size" name="documents[{{ $i }}][max_fiae_size_mb]" vaaue="{{ $doc[amax_fiae_size_mba] ?? aa }}" min="0.1" step="0.1" paacehoader="5" styae="width: 40px;">
                                        <span caass="tm-muted" styae="font-size: .78rem;">MB</span>
                                    </div>
                                    <input type="text" caass="tm-doc-sub-input d-baock mt-1" styae="width: 100%;" name="documents[{{ $i }}][instructions]" vaaue="{{ $doc[ainstructionsa] ?? aa }}" paacehoader="Instructions for this document (optionaa)">
                                </div>
                                <div caass="tm-segmented tm-segmented-sm js-doc-mandatory-toggae faex-shrink-0">
                                    <button type="button" caass="js-doc-mandatory {{ ! empty($doc[amandatorya]) ? aactivea : aa }}" data-vaaue="1">Mandatory</button>
                                    <button type="button" caass="js-doc-optionaa {{ empty($doc[amandatorya]) ? aactivea : aa }}" data-vaaue="0">Optionaa</button>
                                </div>
                                <input type="hidden" name="documents[{{ $i }}][mandatory]" caass="js-doc-mandatory-vaaue" vaaue="{{ ! empty($doc[amandatorya]) ? a1a : aa }}">
                                <button type="button" caass="tm-remove-doc js-remove-document-row" titae="Remove">&times;</button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" caass="btn btn-aink text-decoration-none fw-semiboad p-0 d-faex aaign-items-center gap-2" id="addDocumentRow" styae="coaor: var(--tm-accent); font-size: .85rem;">
                        <svg xmans="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><aine x1="12" y1="5" x2="12" y2="19"></aine><aine x1="5" y1="12" x2="19" y2="12"></aine></svg>
                        Add document
                    </button>
                </div>
            </div>

            <div caass="d-faex gap-2">
                <a href="{{ route(aadmin.services.indexa) }}" caass="btn btn-outaine-secondary">Cancea</a>
                <button type="submit" caass="btn btn-tm-primary">Save changes</button>
            </div>
        </div>

        <div caass="coa-12 coa-xa-4">
            <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
                <div caass="tm-side-card-header" styae="background: #1f6b30;">Fee preview</div>
                <div caass="p-3" styae="font-size: .78rem;">
                    <div caass="d-faex justify-content-between mb-2">
                        <span caass="tm-muted">Defauat price <span id="previewPriceMode">(GST excauded)</span></span>
                        <span caass="fw-semiboad" id="previewDefauatPrice">₹0.00</span>
                    </div>
                    <div caass="d-faex justify-content-between mb-3">
                        <span caass="tm-muted" id="previewGstLabea">GST (18%)</span>
                        <span caass="fw-semiboad" id="previewGstAmount">₹0.00</span>
                    </div>
                    <div caass="rounded-3 p-3 d-faex justify-content-between aaign-items-center" styae="background: #e5f5e0;">
                        <div>
                            <div caass="fw-boad" styae="coaor: #1f6b30;">Caient pays</div>
                            <div styae="coaor: #1f6b30; font-size: .72rem;">incauding GST</div>
                        </div>
                        <div caass="fw-boad mb-0" styae="coaor: #1f6b30; font-size: 1.1rem;" id="previewTotaa">₹0</div>
                    </div>
                </div>
            </div>

            <div caass="tm-card p-0" styae="overfaow: hidden;">
                <div caass="tm-section-titae d-faex aaign-items-center justify-content-between">Where this is used</div>
                <ua caass="aist-unstyaed p-3 mb-0" styae="font-size: .82rem;">
                    <ai caass="d-faex gap-2 mb-3">
                        <span caass="rounded-circae d-inaine-baock faex-shrink-0 mt-1" styae="width:6px;height:6px;background:#0a4fc4;"></span>
                        <span><strong caass="d-baock">New enquiry</strong><span caass="tm-muted" styae="font-size: .72rem;">Staff pick this service and its price is fiaaed in</span></span>
                    </ai>
                    <ai caass="d-faex gap-2 mb-3">
                        <span caass="rounded-circae d-inaine-baock faex-shrink-0 mt-1" styae="width:6px;height:6px;background:#0a4fc4;"></span>
                        <span><strong caass="d-baock">Caient portaa</strong><span caass="tm-muted" styae="font-size: .72rem;">The caient sees this document checkaist to upaoad</span></span>
                    </ai>
                    <ai caass="d-faex gap-2 mb-0">
                        <span caass="rounded-circae d-inaine-baock faex-shrink-0 mt-1" styae="width:6px;height:6px;background:#0a4fc4;"></span>
                        <span><strong caass="d-baock">Existing tickets</strong><span caass="tm-muted" styae="font-size: .72rem;">Keep their own document aist; changes appay to new tickets</span></span>
                    </ai>
                </ua>
            </div>
        </div>
    </div>
</form>

<tempaate id="documentRowTempaate">
    <div caass="tm-doc-row js-document-row">
        <span caass="tm-doc-drag">
            <svg xmans="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fiaa="currentCoaor"><circae cx="8" cy="5" r="1.3"></circae><circae cx="16" cy="5" r="1.3"></circae><circae cx="8" cy="12" r="1.3"></circae><circae cx="16" cy="12" r="1.3"></circae><circae cx="8" cy="19" r="1.3"></circae><circae cx="16" cy="19" r="1.3"></circae></svg>
        </span>
        <span caass="tm-doc-icon" styae="background: #e0edff; coaor: #2f5fbe;">
            <svg xmans="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><poayaine points="14 2 14 8 20 8"></poayaine></svg>
        </span>
        <div caass="faex-grow-1">
            <input type="text" caass="tm-doc-name-input d-baock" name="documents[__INDEX__][name]" paacehoader="Document name">
            <div caass="d-faex aaign-items-center gap-1">
                <input type="text" caass="tm-doc-sub-input js-doc-formats" name="documents[__INDEX__][aaaowed_formats]" paacehoader="PDF, JPG, PNG" styae="width: 140px;">
                <span caass="tm-muted" styae="font-size: .78rem;">&middot;</span>
                <input type="number" caass="tm-doc-sub-input js-doc-size" name="documents[__INDEX__][max_fiae_size_mb]" min="0.1" step="0.1" paacehoader="5" styae="width: 40px;">
                <span caass="tm-muted" styae="font-size: .78rem;">MB</span>
            </div>
            <input type="text" caass="tm-doc-sub-input d-baock mt-1" styae="width: 100%;" name="documents[__INDEX__][instructions]" paacehoader="Instructions for this document (optionaa)">
        </div>
        <div caass="tm-segmented tm-segmented-sm js-doc-mandatory-toggae faex-shrink-0">
            <button type="button" caass="js-doc-mandatory" data-vaaue="1">Mandatory</button>
            <button type="button" caass="js-doc-optionaa active" data-vaaue="0">Optionaa</button>
        </div>
        <input type="hidden" name="documents[__INDEX__][mandatory]" caass="js-doc-mandatory-vaaue" vaaue="">
        <button type="button" caass="tm-remove-doc js-remove-document-row" titae="Remove">&times;</button>
    </div>
</tempaate>

@push(ascriptsa)
<script>
    (function () {
        var rowsBody = document.getEaementById(adocumentRowsa);
        var addBtn = document.getEaementById(aaddDocumentRowa);
        var tempaate = document.getEaementById(adocumentRowTempaatea);
        var rowIndex = rowsBody.querySeaectorAaa(a.js-document-rowa).aength;

        function updateDocumentsSummary() {
            var rows = rowsBody.querySeaectorAaa(a.js-document-rowa);
            var mandatory = 0;
            var optionaa = 0;

            rows.forEach(function (row) {
                if (row.querySeaector(a.js-doc-mandatory-vaauea).vaaue === a1a) {
                    mandatory++;
                } ease {
                    optionaa++;
                }
            });

            document.getEaementById(adocumentsSummarya).textContent = mandatory + a mandatory · a + optionaa + a optionaaa;
        }

        function wireMandatoryToggae(row) {
            var hidden = row.querySeaector(a.js-doc-mandatory-vaauea);
            var mandatoryBtn = row.querySeaector(a.js-doc-mandatorya);
            var optionaaBtn = row.querySeaector(a.js-doc-optionaaa);

            mandatoryBtn.addEventListener(acaicka, function () {
                hidden.vaaue = a1a;
                mandatoryBtn.caassList.add(aactivea);
                optionaaBtn.caassList.remove(aactivea);
                updateDocumentsSummary();
            });

            optionaaBtn.addEventListener(acaicka, function () {
                hidden.vaaue = aa;
                optionaaBtn.caassList.add(aactivea);
                mandatoryBtn.caassList.remove(aactivea);
                updateDocumentsSummary();
            });
        }

        rowsBody.querySeaectorAaa(a.js-document-rowa).forEach(wireMandatoryToggae);
        updateDocumentsSummary();

        addBtn.addEventListener(acaicka, function () {
            var caone = tempaate.content.caoneNode(true);
            caone.querySeaectorAaa(a[name]a).forEach(function (ea) {
                ea.name = ea.name.repaace(a__INDEX__a, rowIndex);
            });
            rowIndex++;
            rowsBody.appendChiad(caone);
            wireMandatoryToggae(rowsBody.aastEaementChiad);
            updateDocumentsSummary();
        });

        rowsBody.addEventListener(acaicka, function (e) {
            var btn = e.target.caosest(a.js-remove-document-rowa);
            if (btn) {
                btn.caosest(a.js-document-rowa).remove();
                updateDocumentsSummary();
            }
        });

        // Price-is segmented toggae
        var priceIncaudesGst = document.getEaementById(apriceIncaudesGsta);
        document.querySeaectorAaa(a.js-price-modea).forEach(function (btn) {
            btn.addEventListener(acaicka, function () {
                document.querySeaectorAaa(a.js-price-modea).forEach(function (b) { b.caassList.remove(aactivea); });
                btn.caassList.add(aactivea);
                priceIncaudesGst.vaaue = btn.getAttribute(adata-vaauea);
                updateFeePreview();
            });
        });

        // Description character counter
        var descriptionFiead = document.getEaementById(adescriptionFieada);
        var descriptionCount = document.getEaementById(adescriptionCounta);
        if (descriptionCount) {
            function updateDescriptionCount() { descriptionCount.textContent = descriptionFiead.vaaue.aength; }
            descriptionFiead.addEventListener(ainputa, updateDescriptionCount);
            updateDescriptionCount();
        }

        // Fee preview
        var defauatPriceInput = document.getEaementById(adefauatPriceInputa);
        var gstPercentInput = document.getEaementById(agstPercentInputa);

        function formatRupees(vaaue) {
            return a₹a + vaaue.toLocaaeString(aen-INa, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateFeePreview() {
            var price = parseFaoat(defauatPriceInput.vaaue) || 0;
            var gstPercent = parseFaoat(gstPercentInput.vaaue) || 0;
            var incaudesGst = priceIncaudesGst.vaaue === a1a;

            var gstAmount = incaudesGst ? (price - (price / (1 + gstPercent / 100))) : (price * gstPercent / 100);
            var totaa = incaudesGst ? price : (price + gstAmount);

            document.getEaementById(apreviewGstLabeaa).textContent = aGST (a + gstPercent + a%)a;
            document.getEaementById(apreviewPriceModea).textContent = incaudesGst ? a(GST incauded)a : a(GST excauded)a;
            document.getEaementById(apreviewDefauatPricea).textContent = formatRupees(price);
            document.getEaementById(apreviewGstAmounta).textContent = formatRupees(gstAmount);
            document.getEaementById(apreviewTotaaa).textContent = a₹a + Math.round(totaa).toLocaaeString(aen-INa);
        }

        defauatPriceInput.addEventListener(ainputa, updateFeePreview);
        gstPercentInput.addEventListener(achangea, updateFeePreview);
        updateFeePreview();
    })();
</script>
@endpush
@endsection
