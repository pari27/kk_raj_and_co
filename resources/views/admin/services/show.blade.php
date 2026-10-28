@extends('layouts.app')

@section('title', $service->name . ' — ' . config('app.name', 'Task Management'))


@section('content')
@php
    $mandatoryCount = $service->documents->where('is_mandatory', true)->count();
    $optionalCount = $service->documents->count() - $mandatoryCount;
    $gstPercentLabel = rtrim(rtrim(number_format((float) $service->gst_percent, 2), '0'), '.');
    $totalFee = $service->totalFee();
    $gstAmount = $service->gstAmount();
    $basePrice = $totalFee - $gstAmount;

    $dotColor = function (string $action): string {
        return match ($action) {
            'Created', 'Activated' => '#1f6b30',
            'Deactivated' => '#7f1616',
            default => '#0a4fc4',
        };
    };
@endphp

<div id="service-show-page">
    <x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Services', 'url' => route('admin.services.index')], ['label' => $service->name]]" />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-3 tm-divider-gold">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="tm-serif fw-bold mb-0 u-e83e11">{{ $service->name }}</h1>
                <span class="badge rounded-pill {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div class="tm-muted u-5a305f">
                Created {{ $service->created_at->format('d M Y') }} by {{ $service->createdBy?->name ?? 'System' }}
                <span class="mx-1">&middot;</span>
                Last updated {{ $service->updated_at->format('d M Y') }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            
            <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-tm-primary">Edit service</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="tm-card svc-stat-card p-3 u-d30963">
                <div class="small mb-2 opacity-75">Total fee</div>
                <div class="h3 fw-bold mb-1">₹{{ number_format($totalFee) }}</div>
                <div class="small opacity-75 u-31578e">{{ $service->price_includes_gst ? 'GST included' : 'GST added' }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card svc-stat-card p-3 u-a1d6bd">
                <div class="small mb-2 opacity-75">Documents</div>
                <div class="h3 fw-bold mb-1">{{ $service->documents->count() }}</div>
                <div class="small opacity-75 u-31578e">{{ $mandatoryCount }} mandatory &middot; {{ $optionalCount }} optional</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card svc-stat-card p-3 u-adf4bb">
                <div class="small mb-2 opacity-75">Open services</div>
                <div class="h3 fw-bold mb-1">{{ number_format($openTicketsCount) }}</div>
                <div class="small opacity-75 u-31578e">in progress</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card svc-stat-card p-3 u-42af9e">
                <div class="small mb-2 opacity-75">Completed</div>
                <div class="h3 fw-bold mb-1">{{ number_format($completedTicketsCount) }}</div>
                <div class="small opacity-75 u-31578e">this FY</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            @if ($service->description)
                <div class="tm-card p-3 mb-3 d-flex align-items-start gap-3 u-e95c22">
                    <span class="svc-doc-icon flex-shrink-0 u-a53131">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </span>
                    <div>
                        <div class="fw-bold u-8ae449">Description</div>
                        <div class="tm-muted u-8ae449">{{ $service->description }}</div>
                    </div>
                </div>
            @endif

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="svc-section-title d-flex align-items-center justify-content-between">
                    <h2 class="mb-0 fw-bold">Documents required ({{ $service->documents->count() }})</h2>
                    <span class="u-48932b">{{ $mandatoryCount }} mandatory &middot; {{ $optionalCount }} optional</span>
                </div>
                @if ($service->documents->isEmpty())
                    <div class="p-3"><x-empty-state title="No documents configured" description="Edit this service to add the documents clients must submit." /></div>
                @else
                    <div class="table-responsive">
                        <table id="documentsRequiredTable" class="table tm-table align-middle mb-0">
                            <thead><tr><th>Document</th><th>Instructions</th><th>Formats</th><th>Max size</th><th>Type</th></tr></thead>
                            <tbody>
                                @foreach ($service->documents as $document)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="svc-doc-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                                </span>
                                                <span class="fw-semibold">{{ $document->name }}</span>
                                            </div>
                                        </td>
                                        <td class="tm-muted">{{ $document->instructions ?: '—' }}</td>
                                        <td>
                                            @if ($document->isText())
                                                <div class="d-flex flex-wrap gap-1"><span class="svc-format-chip">Text</span></div>
                                            @elseif ($document->allowed_formats)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach (explode(',', $document->allowed_formats) as $format)
                                                        <span class="svc-format-chip">{{ trim($format) }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="tm-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $document->max_file_size_kb ? number_format($document->max_file_size_kb / 1024, 1) . ' MB' : '—' }}</td>
                                        <td><span class="badge rounded-pill {{ $document->is_mandatory ? 'text-bg-danger' : 'text-bg-secondary' }}">{{ $document->is_mandatory ? 'Mandatory' : 'Optional' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="svc-section-title d-flex align-items-center justify-content-between">
                    <h2 class="mb-0 fw-bold">Change history</h2>
                </div>
                <div class="px-4 pb-2 pt-1">
                    @forelse ($service->activityLogs as $log)
                        <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <span class="rounded-circle flex-shrink-0 mt-1" style="width: 8px; height: 8px; background: {{ $dotColor($log->action) }};"></span>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $log->details ?? $log->action }}</div>
                                <div class="tm-muted u-cc37c5">{{ $log->created_at->format('j M Y, g:i A') }}{{ $log->user ? ' · '.$log->user->name : '' }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="tm-muted small mb-0">No activity recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="svc-side-heading u-1fcbae"><h2 class="h6 fw-bold mb-0">Fee summary</h2></div>
                <div class="px-3 pb-2 pt-1">
                    <div class="svc-data-row d-flex justify-content-between gap-3 py-2"><span class="svc-info-label">Base price</span><strong>₹{{ number_format($basePrice, 2) }}</strong></div>
                    <div class="svc-data-row d-flex justify-content-between gap-3 py-2"><span class="svc-info-label">GST ({{ $gstPercentLabel }}%)</span><strong>₹{{ number_format($gstAmount, 2) }}</strong></div>
                    <div class="mt-3 p-3 rounded d-flex justify-content-between align-items-center gap-2 u-389ec9">
                        <div class="svc-fee-label"><strong class="u-f63532">Total fee</strong><div class="u-f63532">{{ $service->price_includes_gst ? 'GST included' : 'GST added' }}</div></div>
                        <strong class="fs-5 u-f63532">₹{{ number_format($totalFee, 2) }}</strong>
                    </div>
                    <p class="tm-muted svc-fee-note mb-0 mt-2">Admin can change the price on an enquiry.</p>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="svc-side-heading u-8b012c"><h2 class="h6 fw-bold mb-0">Service details</h2></div>
                <div class="px-3 pb-2 pt-1">
                    <div class="svc-data-row d-flex justify-content-between align-items-center gap-3 py-2"><span class="svc-info-label">Status</span><span class="badge rounded-pill {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></div>
                    <div class="svc-data-row d-flex justify-content-between gap-3 py-2"><span class="svc-info-label">Documents</span><strong>{{ $mandatoryCount }} mandatory, {{ $optionalCount }} optional</strong></div>
                    <div class="svc-data-row d-flex justify-content-between gap-3 py-2"><span class="svc-info-label">GST rate</span><strong>{{ $gstPercentLabel }}%</strong></div>
                </div>
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="svc-side-heading u-e25d39"><h2 class="h6 fw-bold mb-0">Usage this FY</h2></div>
                <div class="px-3 pb-3 svc-usage-body">
                    <div class="d-flex gap-2 mb-3">
                        <div class="svc-mini-stat u-5c1733">
                            <div class="small u-fb13a0">Open</div>
                            <div class="fw-bold u-fb13a0">{{ number_format($openTicketsCount) }}</div>
                        </div>
                        <div class="svc-mini-stat u-389ec9">
                            <div class="small u-f63532">Completed</div>
                            <div class="fw-bold u-f63532">{{ number_format($completedTicketsCount) }}</div>
                        </div>
                        <div class="svc-mini-stat u-d0dd3d">
                            <div class="small u-042b8f">Fees</div>
                            <div class="fw-bold u-042b8f">₹{{ number_format($feesThisFy) }}</div>
                        </div>
                    </div>
                    <a href="{{ route('tickets.index') }}" class="svc-usage-link">View {{ $service->name }} services &rarr;</a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
