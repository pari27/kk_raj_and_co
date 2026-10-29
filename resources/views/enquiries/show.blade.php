@extends('layouts.app')

@section('title', $enquiry->number . ' — ' . config('app.name', 'Task Management'))


@section('content')
@php
    $statCards = [
        ['label' => 'Enquiry total', 'value' => '₹'.number_format((float) $enquiry->total), 'caption' => 'including GST', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Services', 'value' => (string) $enquiry->tickets->count(), 'caption' => $closedTicketsCount.' closed', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Fees received', 'value' => '₹'.number_format($feesReceived), 'caption' => $feesReceivedCount > 0 ? $feesReceivedCount.' payment(s)' : 'no payments yet', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Fees pending', 'value' => '₹'.number_format($feesPending), 'caption' => 'across '.$enquiry->tickets->count().' service(s)', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
    ];

    $portalLabels = [
        'activated' => ['text' => 'Portal: activated', 'color' => '#1f6b30'],
        'invite_sent' => ['text' => 'Portal: invite sent', 'color' => '#2f5fbe'],
        'not_logged_in' => ['text' => 'Portal: not logged in yet', 'color' => '#b9650a'],
    ];
    $portalInfo = $portalLabels[$enquiry->customer->portal_status] ?? $portalLabels['not_logged_in'];

    $words = preg_split('/\s+/', trim($enquiry->customer->name));
    $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));

    $dotColor = function (string $action): string {
        return match ($action) {
            'Created', 'Activated' => '#1f6b30',
            'Deactivated' => '#7f1616',
            default => '#0a4fc4',
        };
    };
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Enquiries', 'url' => route('enquiries.index')], ['label' => $enquiry->number]]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
            <h1 class="tm-serif fw-bold mb-0 u-e83e11">{{ $enquiry->number }}</h1>
            <span class="badge rounded-pill text-bg-{{ $enquiry->status === 'Open' ? 'primary' : 'secondary' }} fw-normal">{{ $enquiry->status }}</span>
        </div>
        <p class="tm-muted mb-0 u-5a305f">
            Created {{ $enquiry->created_at->format('j M Y, g:i A') }}{{ $enquiry->createdBy ? ' by '.$enquiry->createdBy->name : '' }}
            &middot; Last updated {{ $enquiry->updated_at->diffForHumans() }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ Add Enquiry</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle u-943986"></span>
                <span class="position-absolute rounded-circle u-e46b84"></span>
                <div class="position-relative">
                    <div class="small mb-2 u-bbebd4">{{ $card['label'] }}</div>
                    <div class="h4 tm-serif fw-bold mb-1 text-white">{{ $card['value'] }}</div>
                    <div class="small u-48932b">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="tm-card p-0 mb-3 u-abf627">
            <div class="p-2 px-3 u-8b012c">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Client</h2>
            </div>
            <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 u-2e984d">
                        {{ $initials }}
                    </div>
                    <div>
                        <div class="fw-bold enquiry-client-name">{{ $enquiry->customer->name }}</div>
                        <div class="tm-muted u-5a305f">
                            &#128222; {{ $enquiry->customer->phone }} &nbsp; &#9993; {{ $enquiry->customer->email }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="mb-1" style="font-size: .72rem; color: {{ $portalInfo['color'] }};">
                        <span class="rounded-circle d-inline-block me-1" style="width: 6px; height: 6px; background: {{ $portalInfo['color'] }};"></span>{{ $portalInfo['text'] }}
                    </div>
                    <a href="{{ route('customers.show', $enquiry->customer) }}" class="small text-decoration-none fw-semibold enquiry-view-client-link">View client &rarr;</a>
                </div>
            </div>
        </div>

        <div class="tm-card p-0 mb-3 u-abf627">
            <div class="d-flex align-items-center justify-content-between p-2 px-3 u-8b012c">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Services in this enquiry ({{ $enquiry->tickets->count() }})</h2>
                @if ($canAddService)
                    <button type="button" class="enquiry-add-service-link" data-bs-toggle="modal" data-bs-target="#addServiceModal">+ Add service</button>
                @else
                    <span class="enquiry-ticket-heading-note u-f3c187">One entry per service</span>
                @endif
            </div>
            @if ($enquiry->tickets->isEmpty())
                <div class="p-4">
                    <x-empty-state title="No services yet" description="Services created for this enquiry will show up here." />
                </div>
            @else
                <div class="table-responsive">
                    <table id="enquiryTicketsTable" class="table tm-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Assigned to</th>
                                <th>Status</th>
                                
                                <th class="text-end">Price</th>
                                @if (auth()->user()->isAdmin())<th class="text-end">Action</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enquiry->tickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-decoration-none enquiry-ticket-number">{{ $ticket->number }}</a>
                                        <div class="tm-muted enquiry-ticket-service">{{ $ticket->service?->name }}</div>
                                    </td>
                                    <td class="enquiry-ticket-assignee">{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                                    <td><x-status-badge :status="$ticket->status->value" /></td>
                                    <td class="text-end fw-semibold">₹{{ number_format((float) $ticket->total) }}</td>
                                    @if (auth()->user()->isAdmin())
                                        @php $deleteBlocker = $ticket->deletionBlocker(); @endphp
                                        <td class="text-end">
                                            @if ($deleteBlocker)
                                                <span class="enquiry-delete-service is-disabled" title="{{ $deleteBlocker }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="d-inline js-confirm-delete" data-confirm="Delete {{ $ticket->number }} ({{ $ticket->service?->name }})? This can't be undone.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="enquiry-delete-service" title="Delete this service" aria-label="Delete {{ $ticket->number }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tm-card p-0 u-abf627">
            <div class="p-2 px-3 u-8b012c">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Activity</h2>
            </div>
            <div class="p-4">
                @forelse ($activity as $log)
                    <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                        <span class="rounded-circle flex-shrink-0 mt-1" style="width: 8px; height: 8px; background: {{ $dotColor($log->action) }};"></span>
                        <div class="flex-grow-1">
                            <div class="small fw-semibold activity-list-title">{{ $log->details ?? $log->action }}</div>
                            <div class="tm-muted u-cc37c5">{{ $log->created_at->format('j M Y, g:i A') }}{{ $log->user ? ' · '.$log->user->name : '' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="tm-muted small mb-0">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="tm-card p-0 mb-3 u-abf627">
            <div class="d-flex align-items-center justify-content-between gap-2 p-2 px-3 u-1fcbae">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Fee breakdown</h2>
                @if ($feesPending > 0)
                    <a href="#enquiryPaymentModal" role="button" data-bs-toggle="modal" class="enquiry-make-payment-button flex-shrink-0">Record payment</a>
                @endif
            </div>
            <ul class="list-unstyled p-3 mb-0 u-8ae449">
                @foreach ($enquiry->tickets as $ticket)
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="tm-muted">{{ $ticket->service?->name }}</span>
                        <span class="fw-bold">₹{{ number_format((float) $ticket->price) }}</span>
                    </li>
                @endforeach
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">GST</span>
                    <span class="fw-bold">₹{{ number_format((float) $enquiry->gst_total) }}</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Discount</span>
                    <span class="fw-bold">₹{{ number_format((float) $enquiry->discount) }}</span>
                </li>
            </ul>
            <div class="mx-3 mb-2 rounded-3 p-3 d-flex justify-content-between align-items-center u-389ec9">
                <div>
                    <div class="fw-bold">Total</div>
                    <div class="tm-muted u-31578e">including GST</div>
                </div>
                <div class="h5 tm-serif fw-bold mb-0 u-f63532">₹{{ number_format((float) $enquiry->total) }}</div>
            </div>
            <div class="mx-3 mb-3 rounded-3 p-3 d-flex justify-content-between align-items-center u-57f634">
                <div class="fw-bold u-a8cc8a">Pending</div>
                <div class="h6 fw-bold mb-0 u-a8cc8a">₹{{ number_format($feesPending) }}</div>
            </div>
        </div>

        <div class="tm-card p-0 mb-3 u-abf627">
            <div class="p-2 px-3 u-8b012c">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Enquiry details</h2>
            </div>
            <ul class="list-unstyled p-3 mb-0 enquiry-details-list">
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Enquiry no.</span>
                    <span class="fw-bold">{{ $enquiry->number }}</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Created by</span>
                    <span class="fw-bold">{{ $enquiry->createdBy->name ?? '—' }}</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Created on</span>
                    <span class="fw-bold">{{ $enquiry->created_at->format('j M Y') }}</span>
                </li>
                <li class="d-flex justify-content-between py-2">
                    <span class="tm-muted">Status</span>
                    <span class="fw-bold" style="color: {{ $enquiry->status === 'Open' ? '#2f5fbe' : 'var(--tm-muted)' }};">{{ $enquiry->status }}</span>
                </li>
            </ul>
        </div>

        @if ($enquiry->notes)
            <div class="tm-card p-0 u-abf627">
                <div class="p-2 px-3 u-1fcbae">
                    <h2 class="h6 tm-serif fw-bold mb-0 text-white">Notes</h2>
                </div>
                <div class="p-3 small">{{ $enquiry->notes }}</div>
            </div>
        @endif
    </div>
</div>



@if ($canAddService)
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered add-service-dialog">
            <div class="modal-content add-service-content">
                <form method="POST" action="{{ route('enquiries.services.store', $enquiry) }}" id="addServiceForm">
                    @csrf

                    <div class="add-service-head">
                        <span class="add-service-head-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        </span>
                        <div class="flex-grow-1">
                            <h2 class="add-service-title" id="addServiceModalLabel">Add a service</h2>
                            <div class="add-service-sub">{{ $enquiry->number }} &middot; {{ $enquiry->customer->name }} &middot; {{ $enquiry->tickets->count() }} {{ \Illuminate\Support\Str::plural('service', $enquiry->tickets->count()) }} so far</div>
                        </div>
                        <button type="button" class="add-service-close" data-bs-dismiss="modal" aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                        </button>
                    </div>

                    <div class="add-service-body">
                        <label for="addServiceSelect" class="add-service-label">Service <span class="text-danger">*</span></label>
                        <div class="add-service-select">
                            <span class="add-service-select-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            </span>
                            <select id="addServiceSelect" name="service_id" required>
                                <option value="">Choose a service</option>
                                @foreach ($addService['services'] as $service)
                                    <option value="{{ $service['id'] }}" @selected((string) old('service_id') === (string) $service['id'])>{{ $service['name'] }}</option>
                                @endforeach
                            </select>
                            <svg class="add-service-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                        @error('service_id')<div class="add-service-error">{{ $message }}</div>@enderror
                        @if (empty($addService['services']))
                            <div class="add-service-hint">Every available service is already in this enquiry.</div>
                        @endif

                        <div class="add-service-docs d-none" id="addServiceDocs">
                            <div class="add-service-docs-line">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                <span id="addServiceDocsCount"></span>
                            </div>
                            <div class="add-service-chips" id="addServiceChips"></div>
                        </div>

                        <div class="row g-3 add-service-row">
                            <div class="col-sm-6">
                                <label for="addServicePrice" class="add-service-label">Price</label>
                                <div class="add-service-price">
                                    <span>&#8377;</span>
                                    <input id="addServicePrice" type="number" step="0.01" min="0" name="price" value="{{ old('price') }}">
                                </div>
                                @error('price')<div class="add-service-error">{{ $message }}</div>@enderror
                                <div class="add-service-hint" id="addServicePriceHint">Choose a service to see its price.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="add-service-label" id="addServiceAssigneeLabel">Assign to</label>
                                <input type="hidden" name="assigned_to" id="addServiceAssignee" value="{{ old('assigned_to') }}">
                                <div class="dropdown">
                                    <button type="button" class="add-service-assign" data-bs-toggle="dropdown" aria-expanded="false" aria-labelledby="addServiceAssigneeLabel">
                                        <span id="addServiceAssignAvatar"></span>
                                        <span class="add-service-assign-name" id="addServiceAssignName">Unassigned</span>
                                        <span class="add-service-assign-open" id="addServiceAssignOpen"></span>
                                        <svg class="add-service-chevron-inline" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <ul class="dropdown-menu add-service-assign-menu" id="addServiceAssignMenu"></ul>
                                </div>
                                @error('assigned_to')<div class="add-service-error">{{ $message }}</div>@enderror
                                <div class="add-service-hint">Shows how many open services each person has</div>
                            </div>
                        </div>

                        <div class="add-service-summary d-none" id="addServiceSummary">
                            <div>
                                <div class="add-service-summary-title">New service {{ $addService['next_number'] }} will be created</div>
                                <div class="add-service-summary-line" id="addServiceSummaryLine"></div>
                            </div>
                            <div class="add-service-summary-total" id="addServiceSummaryTotal"></div>
                        </div>

                        <div class="add-service-mail">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            @if ($addService['client_gets_email'])
                                {{ $addService['client_first_name'] }} will get an email with the new document list.
                            @else
                                {{ $addService['client_first_name'] }} has no email updates switched on, so no email will be sent.
                            @endif
                        </div>
                    </div>

                    <div class="add-service-foot">
                        <button type="button" class="add-service-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="add-service-submit" id="addServiceSubmit" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                            Add service
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var services = @json($addService['services']);
                var assignees = @json($addService['assignees']);
                var enquiryTotal = @json($addService['enquiry_total']);

                var select = document.getElementById('addServiceSelect');
                var price = document.getElementById('addServicePrice');
                var docs = document.getElementById('addServiceDocs');
                var docsCount = document.getElementById('addServiceDocsCount');
                var chips = document.getElementById('addServiceChips');
                var priceHint = document.getElementById('addServicePriceHint');
                var summary = document.getElementById('addServiceSummary');
                var summaryLine = document.getElementById('addServiceSummaryLine');
                var summaryTotal = document.getElementById('addServiceSummaryTotal');
                var submit = document.getElementById('addServiceSubmit');
                var assignInput = document.getElementById('addServiceAssignee');
                var assignMenu = document.getElementById('addServiceAssignMenu');
                var assignAvatar = document.getElementById('addServiceAssignAvatar');
                var assignName = document.getElementById('addServiceAssignName');
                var assignOpen = document.getElementById('addServiceAssignOpen');

                function money(amount) {
                    return '₹' + Number(amount).toLocaleString('en-IN', { maximumFractionDigits: 2 });
                }

                function chosenService() {
                    return services.filter(function (item) { return String(item.id) === select.value; })[0] || null;
                }

                // The same maths the server does when it saves the service.
                function breakdown(service, typed) {
                    var gst = service.includes_gst
                        ? Math.round((typed - typed / (1 + service.gst_percent / 100)) * 100) / 100
                        : Math.round(typed * service.gst_percent) / 100;
                    var total = service.includes_gst ? typed : Math.round((typed + gst) * 100) / 100;

                    return { gst: gst, total: total, net: Math.round((total - gst) * 100) / 100 };
                }

                function refresh(fillPrice) {
                    var service = chosenService();
                    submit.disabled = !service;

                    if (!service) {
                        docs.classList.add('d-none');
                        summary.classList.add('d-none');
                        priceHint.textContent = 'Choose a service to see its price.';
                        return;
                    }

                    if (fillPrice) { price.value = service.price; }

                    docs.classList.remove('d-none');
                    docsCount.textContent = service.documents.length
                        ? service.documents.length + (service.documents.length === 1 ? ' document needed' : ' documents needed')
                        : 'No documents needed';
                    chips.textContent = '';
                    service.documents.forEach(function (name) {
                        var chip = document.createElement('span');
                        chip.textContent = name;
                        chips.appendChild(chip);
                    });

                    priceHint.textContent = 'Default from Services: ' + money(service.price) + ' · '
                        + (service.includes_gst ? 'GST included' : '+ ' + service.gst_percent + '% GST');

                    var typed = parseFloat(price.value);
                    if (isNaN(typed) || typed < 0) { summary.classList.add('d-none'); return; }

                    var parts = breakdown(service, typed);
                    summaryLine.textContent = money(parts.net) + ' + ' + money(parts.gst) + ' GST · enquiry total becomes ' + money(enquiryTotal + parts.total);
                    summaryTotal.textContent = money(parts.total);
                    summary.classList.remove('d-none');
                }

                function avatar(person) {
                    var el = document.createElement('span');
                    el.className = 'add-service-avatar';
                    if (person) {
                        el.textContent = person.initials;
                        el.style.background = person.color.bg;
                        el.style.color = person.color.text;
                    } else {
                        el.textContent = '?';
                    }
                    return el;
                }

                function choose(person) {
                    assignInput.value = person ? person.id : '';
                    assignName.textContent = person ? person.label : 'Unassigned';
                    assignOpen.textContent = person ? person.open + ' open' : '';
                    assignAvatar.textContent = '';
                    assignAvatar.appendChild(avatar(person));
                }

                function addOption(person) {
                    var li = document.createElement('li');
                    var link = document.createElement('a');
                    link.href = '#';
                    link.className = 'dropdown-item add-service-assign-item';
                    link.appendChild(avatar(person));
                    var name = document.createElement('span');
                    name.className = 'add-service-assign-name';
                    name.textContent = person ? person.label : 'Unassigned';
                    link.appendChild(name);
                    if (person) {
                        var open = document.createElement('span');
                        open.className = 'add-service-assign-open';
                        open.textContent = person.open + ' open';
                        link.appendChild(open);
                    }
                    link.addEventListener('click', function (event) { event.preventDefault(); choose(person); });
                    li.appendChild(link);
                    assignMenu.appendChild(li);
                }

                addOption(null);
                assignees.forEach(addOption);

                var oldAssignee = assignees.filter(function (person) { return String(person.id) === String(assignInput.value); })[0] || null;
                choose(oldAssignee);

                select.addEventListener('change', function () { refresh(true); });
                price.addEventListener('input', function () { refresh(false); });
                refresh(price.value === '');

                @if ($errors->has('service_id') || $errors->has('price') || $errors->has('assigned_to'))
                    new bootstrap.Modal(document.getElementById('addServiceModal')).show();
                @endif
            })();
        </script>
    @endpush
@endif

@if ($feesPending > 0)
    <div class="modal fade" id="enquiryPaymentModal" tabindex="-1" aria-labelledby="enquiryPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered enquiry-payment-modal-dialog">
            <div class="modal-content enquiry-payment-modal-content">
                <form method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
                    <input type="hidden" name="return_to_enquiry" value="1">
                    <input type="hidden" name="mode" id="enquiryPaymentMode" value="{{ old('mode', 'UPI') }}">

                    <div class="enquiry-payment-modal-header">
                        <span class="enquiry-payment-currency-icon">&#8377;</span>
                        <div class="flex-grow-1">
                            <h2 class="h6 fw-bold mb-0 text-white" id="enquiryPaymentModalLabel">Record payment</h2>
                            <div class="small enquiry-payment-modal-subtitle">Record an advance, partial or full payment for this enquiry</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        @error('enquiry_id')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror
                        <div class="enquiry-payment-summary">
                            <div class="fw-bold">{{ $enquiry->customer->name ?? 'Client' }}</div>
                            <div class="enquiry-payment-ticket">{{ $enquiry->number }}</div>
                            <div class="enquiry-payment-balance">Balance due: <strong>&#8377;{{ number_format($feesPending, 2) }}</strong></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="enquiryPaymentAmount" class="tm-field-label d-block">Amount received <span class="text-danger">*</span></label>
                                <div class="enquiry-payment-amount-field">
                                    <span>&#8377;</span>
                                    <input id="enquiryPaymentAmount" type="number" step="0.01" min="0.01" max="{{ number_format($feesPending, 2, '.', '') }}" name="amount" value="{{ old('amount') }}" class="form-control tm-field @error('amount') is-invalid @enderror" required>
                                </div>
                                @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="enquiryPaymentDate" class="tm-field-label d-block">Payment date <span class="text-danger">*</span></label>
                                <input id="enquiryPaymentDate" type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="form-control tm-field @error('paid_at') is-invalid @enderror" required>
                                @error('paid_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="tm-field-label d-block">Payment mode <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 flex-wrap" id="enquiryPaymentModeButtons">
                                @foreach (['UPI', 'Bank transfer', 'Cash', 'Cheque'] as $mode)
                                    <button type="button" class="btn btn-outline-secondary btn-sm enquiry-payment-mode-button {{ old('mode', 'UPI') === $mode ? 'active' : '' }}" data-mode="{{ $mode }}">{{ $mode }}</button>
                                @endforeach
                            </div>
                            @error('mode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="enquiryPaymentReference" class="tm-field-label d-block" id="enquiryPaymentReferenceLabel">Reference</label>
                            <input id="enquiryPaymentReference" type="text" name="reference" value="{{ old('reference') }}" maxlength="100" class="form-control tm-field @error('reference') is-invalid @enderror">
                            <div class="form-text tm-muted" id="enquiryPaymentReferenceHint"></div>
                            @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="enquiryPaymentNote" class="tm-field-label d-block">Note <span class="tm-muted fw-normal">(optional)</span></label>
                            <textarea id="enquiryPaymentNote" name="note" rows="2" maxlength="500" class="form-control tm-field @error('note') is-invalid @enderror" placeholder="For example, paid by the client's accountant">{{ old('note') }}</textarea>
                            @error('note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="enquiry-payment-partial-note">
                            If you enter less than the balance, it will be saved as a partial payment and the enquiry stays open.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-tm-primary" id="enquiryPaymentSubmitButton">Record payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($feesPending > 0)
    @push('scripts')
        <script>
            (function () {
                var modeInput = document.getElementById('enquiryPaymentMode');
                var modeButtons = document.querySelectorAll('#enquiryPaymentModeButtons .enquiry-payment-mode-button');
                var referenceLabel = document.getElementById('enquiryPaymentReferenceLabel');
                var referenceInput = document.getElementById('enquiryPaymentReference');
                var referenceHint = document.getElementById('enquiryPaymentReferenceHint');
                var referenceLabels = {
                    'UPI': 'UPI reference number',
                    'Bank transfer': 'Bank transfer reference',
                    'Cash': 'Reference',
                    'Cheque': 'Cheque number'
                };

                function selectMode(mode) {
                    modeInput.value = mode;
                    modeButtons.forEach(function (button) {
                        var isActive = button.getAttribute('data-mode') === mode;
                        button.classList.toggle('active', isActive);
                        button.classList.toggle('btn-outline-primary', isActive);
                        button.classList.toggle('btn-outline-secondary', !isActive);
                    });
                    referenceLabel.textContent = referenceLabels[mode] || 'Reference';
                    referenceInput.required = mode !== 'Cash';
                    referenceHint.textContent = mode === 'Cash' ? 'Not needed for cash payments' : '';
                }

                modeButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        selectMode(button.getAttribute('data-mode'));
                    });
                });
                selectMode(modeInput.value || 'UPI');
            })();
        </script>
    @endpush
@endif

@if ($errors->has('enquiry_id') || $errors->has('amount') || $errors->has('mode') || $errors->has('paid_at') || $errors->has('reference') || $errors->has('note'))
    @push('scripts')
        <script>
            var enquiryPaymentModal = document.getElementById('enquiryPaymentModal');
            if (enquiryPaymentModal) {
                bootstrap.Modal.getOrCreateInstance(enquiryPaymentModal).show();
            }
        </script>
    @endpush
@endif
@endsection

@if (auth()->user()->isAdmin())
    @push('scripts')
        <script>
            document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
                });
            });
        </script>
    @endpush
@endif
