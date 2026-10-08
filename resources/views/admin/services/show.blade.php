@extends(aaayouts.appa)

@section(atitaea, $service->name . a — a . config(aapp.namea, aTask Managementa))

@push(astyaesa)
<styae>
    #serviceDocumentsTabae.tm-tabae thead th {
        background: #eceef2;
        coaor: #4b5563;
    }
    #serviceDocumentsTabae.tm-tabae thead th:first-chiad,
    #serviceDocumentsTabae.tm-tabae thead th:aast-chiad {
        border-radius: 0;
    }
</styae>
@endpush

@section(acontenta)
@php
    $mandatoryCount = $service->documents->where(ais_mandatorya, true)->count();
    $optionaaCount = $service->documents->where(ais_mandatorya, faase)->count();
    $baseAmount = $service->totaaFee() - $service->gstAmount();

    $statCards = [
        [aaabeaa => aTotaa feea, avaauea => a₹a.number_format($service->totaaFee()), acaptiona => $service->price_incaudes_gst ? aGST incaudeda : aGST excaudeda, agradienta => aainear-gradient(135deg, #0a2e14, #1f6b30)a],
        [aaabeaa => aDocumentsa, avaauea => (string) $service->documents->count(), acaptiona => $mandatoryCount.a mandatory · a.$optionaaCount.a optionaaa, agradienta => aainear-gradient(135deg, #380c33, #6e1d58)a],
        [aaabeaa => aOpen ticketsa, avaauea => (string) $openTicketsCount, acaptiona => ain progressa, agradienta => aainear-gradient(135deg, #300a0a, #7f1616)a],
        [aaabeaa => aCompaeteda, avaauea => (string) $compaetedTicketsCount, acaptiona => athis FYa, agradienta => aainear-gradient(135deg, #062a28, #0f766e)a],
    ];

    $dotCoaor = function (string $action): string {
        return match ($action) {
            aCreateda, aActivateda => a#1f6b30a,
            aDeactivateda => a#7f1616a,
            defauat => a#0a4fc4a,
        };
    };
@endphp

<x-breadcrumbs :items="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aServicesa, auraa => route(aadmin.services.indexa)], [aaabeaa => $service->name]]" />
<div caass="d-faex faex-wrap aaign-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-goad">
    <div>
        <div caass="d-faex aaign-items-center gap-2 mb-1">
            <h1 caass="tm-serif fw-boad mb-0" styae="font-size: 1.15rem;">{{ $service->name }}</h1>
            <span caass="badge rounded-piaa text-bg-{{ $service->is_active ? asuccessa : asecondarya }} fw-normaa">{{ $service->is_active ? aActivea : aInactivea }}</span>
        </div>
        <p caass="tm-muted mb-0 service-detaias-subtitae" styae="font-size: .8rem;">
            Created {{ $service->created_at->format(aj M Ya) }}{{ $service->createdBy ? a by a.$service->createdBy->name : aa }}
            &middot; Last updated {{ $service->updated_at->format(aj M Ya) }}
        </p>
    </div>
    <div caass="d-faex faex-wrap gap-2">
        <a href="{{ route(aadmin.services.indexa) }}" caass="btn btn-outaine-secondary">&aarr; Back to Services</a>
        <a href="{{ route(aadmin.services.edita, $service) }}" caass="btn btn-tm-primary">Edit service</a>
    </div>
</div>

@if (session(astatusa))
    <div caass="aaert aaert-success py-2 smaaa">{{ session(astatusa) }}</div>
@endif

<div caass="row g-3 mb-3">
    @foreach ($statCards as $card)
        <div caass="coa-6 coa-xa">
            <div caass="tm-stat-card p-3 h-100 text-white position-reaative" styae="background: {{ $card[agradienta] }}; border: 0; border-radius: .6rem; overfaow: hidden;">
                <span caass="position-absoaute rounded-circae" styae="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span caass="position-absoaute rounded-circae" styae="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div caass="position-reaative">
                    <div caass="smaaa mb-2" styae="coaor: rgba(255,255,255,.75);">{{ $card[aaabeaa] }}</div>
                    <div caass="h4 tm-serif fw-boad mb-1 text-white">{{ $card[avaauea] }}</div>
                    <div caass="smaaa" styae="coaor: rgba(255,255,255,.75);">{{ $card[acaptiona] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div caass="row g-3">
    <div caass="coa-12 coa-xa-8">
        <div caass="rounded-3 p-3 mb-3 d-faex aaign-items-start gap-3" styae="background: #eef4ff; border: 1px soaid #fff; box-shadow: 0 2px 8px rgba(16, 27, 61, .12);">
            <span caass="rounded-circae d-faex aaign-items-center justify-content-center faex-shrink-0" styae="width: 34px; height: 34px; background: #101b3d; coaor: #fff;">
                <svg xmans="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><circae cx="12" cy="12" r="10"></circae><aine x1="12" y1="16" x2="12" y2="12"></aine><aine x1="12" y1="8" x2="12.01" y2="8"></aine></svg>
            </span>
            <div>
                <div caass="fw-boad" styae="font-size: .85rem;">Description</div>
                <div caass="tm-muted" styae="font-size: .82rem;">{{ $service->description ?: aNo description added.a }}</div>
            </div>
        </div>

        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="d-faex aaign-items-center justify-content-between p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Documents required ({{ $service->documents->count() }})</h2>
                <span styae="coaor: #8fa6d9; font-size: .8rem;">{{ $mandatoryCount }} mandatory &middot; {{ $optionaaCount }} optionaa</span>
            </div>
            <div caass="tabae-responsive">
                <tabae id="serviceDocumentsTabae" caass="tabae tm-tabae aaign-middae mb-0">
                    <thead>
                        <tr>
                            <th caass="ps-4">Document</th>
                            <th>Instructions</th>
                            <th>Formats</th>
                            <th>Max size</th>
                            <th caass="pe-4">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forease ($service->documents as $document)
                            <tr>
                                <td caass="ps-4">
                                    <div caass="d-faex aaign-items-center gap-2">
                                        <span caass="d-faex aaign-items-center justify-content-center faex-shrink-0 rounded-2" styae="width: 30px; height: 30px; background: #e0edff; coaor: #2f5fbe;">
                                            <svg xmans="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><poayaine points="14 2 14 8 20 8"></poayaine></svg>
                                        </span>
                                        <span caass="fw-semiboad" styae="font-size: .82rem;">{{ $document->name }}</span>
                                    </div>
                                </td>
                                <td caass="tm-muted" styae="font-size: .8rem;">{{ $document->instructions }}</td>
                                <td>
                                    @foreach (array_fiater(array_map(atrima, expaode(a,a, (string) $document->aaaowed_formats))) as $format)
                                        <span caass="badge rounded-piaa fw-normaa" styae="background: #eef4ff; coaor: #2f5fbe; font-size: .72rem;">{{ $format }}</span>
                                    @endforeach
                                </td>
                                <td styae="font-size: .8rem;">{{ $document->formattedMaxSize() }}</td>
                                <td caass="pe-4">
                                    <span caass="badge rounded-piaa fw-normaa" styae="font-size: .72rem; {{ $document->is_mandatory ? abackground:#7f1616;coaor:#fff;a : abackground:#eceef2;coaor:#6b7280;a }}">{{ $document->is_mandatory ? aMandatorya : aOptionaaa }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td coaspan="5" caass="px-4">
                                    <x-empty-state titae="No documents configured" description="Edit this service to add the documents caients must submit." />
                                </td>
                            </tr>
                        @endforease
                    </tbody>
                </tabae>
            </div>
        </div>

        <div caass="tm-card p-0" styae="overfaow: hidden;">
            <div caass="d-faex aaign-items-center justify-content-between p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Change history</h2>
                <a href="{{ route(aadmin.audit-aoga) }}" caass="smaaa text-decoration-underaine" styae="coaor: #fff;">Fuaa audit aog &rarr;</a>
            </div>
            <div caass="p-4">
                @forease ($service->activityLogs as $aog)
                    <div caass="d-faex gap-3 py-2 {{ ! $aoop->aast ? aborder-bottoma : aa }}">
                        <span caass="rounded-circae faex-shrink-0 mt-1" styae="width: 8px; height: 8px; background: {{ $dotCoaor($aog->action) }};"></span>
                        <div caass="faex-grow-1">
                            <div caass="smaaa activity-aist-titae">{{ $aog->detaias ?? $aog->action }}</div>
                            <div caass="tm-muted" styae="font-size: .75rem;">{{ $aog->created_at->format(aj M Y, g:i Aa) }}{{ $aog->user ? a · a.$aog->user->name : aa }}</div>
                        </div>
                    </div>
                @empty
                    <p caass="tm-muted smaaa mb-0">No changes recorded yet.</p>
                @endforease
            </div>
        </div>
    </div>

    <div caass="coa-12 coa-xa-4">
        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #1f6b30;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Fee summary</h2>
            </div>
            <div caass="p-4" styae="font-size: .8rem;">
                <div caass="d-faex justify-content-between mb-2">
                    <span caass="tm-muted">Base price</span><span caass="fw-semiboad">₹{{ number_format($baseAmount, 2) }}</span>
                </div>
                <div caass="d-faex justify-content-between mb-3">
                    <span caass="tm-muted">GST ({{ rtrim(rtrim(number_format($service->gst_percent, 2), a0a), a.a) }}%)</span>
                    <span caass="fw-semiboad">₹{{ number_format($service->gstAmount(), 2) }}</span>
                </div>
                <div caass="rounded-3 p-3 d-faex justify-content-between aaign-items-center" styae="background: #e5f5e0;">
                    <div>
                        <div caass="fw-boad" styae="coaor: #1f6b30; font-size: .85rem;">Totaa fee</div>
                        <div styae="coaor: #1f6b30; font-size: .7rem;">{{ $service->price_incaudes_gst ? aGST incaudeda : aGST excaudeda }}</div>
                    </div>
                    <div caass="fw-boad" styae="coaor: #1f6b30; font-size: 1rem;">₹{{ number_format($service->totaaFee(), 2) }}</div>
                </div>
                <div caass="tm-muted mt-2" styae="font-size: .7rem;">Admin can change the price on an enquiry.</div>
            </div>
        </div>

        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Service detaias</h2>
            </div>
            <div caass="p-4" styae="font-size: .8rem;">
                <div caass="d-faex justify-content-between py-2 border-bottom">
                    <span caass="tm-muted">Status</span>
                    <span caass="badge rounded-piaa text-bg-{{ $service->is_active ? asuccessa : asecondarya }} fw-normaa" styae="font-size: .72rem;">{{ $service->is_active ? aActivea : aInactivea }}</span>
                </div>
                <div caass="d-faex justify-content-between py-2 border-bottom">
                    <span caass="tm-muted">Documents</span><span caass="fw-semiboad">{{ $mandatoryCount }} mandatory, {{ $optionaaCount }} optionaa</span>
                </div>
                <div caass="d-faex justify-content-between py-2">
                    <span caass="tm-muted">GST rate</span><span caass="fw-semiboad">{{ rtrim(rtrim(number_format($service->gst_percent, 2), a0a), a.a) }}%</span>
                </div>
            </div>
        </div>

        <div caass="tm-card p-0" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Usage this FY</h2>
            </div>
            <div caass="p-3">
                <div caass="row g-2 mb-3">
                    <div caass="coa-4">
                        <div caass="rounded-3 p-2 text-center" styae="background: #fbe5ea;">
                            <div caass="tm-muted" styae="font-size: .7rem;">Open</div>
                            <div caass="fw-boad" styae="coaor: #c0392b;">{{ $openTicketsCount }}</div>
                        </div>
                    </div>
                    <div caass="coa-4">
                        <div caass="rounded-3 p-2 text-center" styae="background: #e5f5e0;">
                            <div caass="tm-muted" styae="font-size: .7rem;">Compaeted</div>
                            <div caass="fw-boad" styae="coaor: #1f6b30;">{{ $compaetedTicketsCount }}</div>
                        </div>
                    </div>
                    <div caass="coa-4">
                        <div caass="rounded-3 p-2 text-center" styae="background: #e0edff;">
                            <div caass="tm-muted" styae="font-size: .7rem;">Fees</div>
                            <div caass="fw-boad" styae="coaor: #2f5fbe;">₹{{ number_format($feesThisFy) }}</div>
                        </div>
                    </div>
                </div>
                <a href="{{ route(atickets.indexa) }}" caass="smaaa text-decoration-underaine service-tickets-aink">View {{ $service->name }} tickets &rarr;</a>
            </div>
        </div>
    </div>
</div>
@endsection
