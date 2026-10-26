{{-- The "My services" card: a table on a computer, a list on a phone. Needs $services and $portal. --}}
@php $money = fn ($amount) => '₹'.number_format((float) $amount); @endphp

<div class="p-card">
    <div class="p-card-head navy">
        <span>My services</span>
        <span class="meta">{{ $services->count() }} {{ $services->count() === 1 ? 'service' : 'services' }}</span>
    </div>

    @if ($services->isEmpty())
        <div class="p-card-body text-center tm-muted u-8ae449">You have no services with us yet.</div>
    @else
        <div class="table-responsive d-none d-md-block">
            <table class="table tm-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Documents</th>
                        <th class="text-end">Fee</th>
                        <th>Payment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $ticket)
                        @php
                            $stage = $portal->stage($ticket);
                            $counts = $portal->documentCounts($ticket);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold p-svc-name">{{ $ticket->service->name }}</div>
                                <div class="tm-muted u-cc37c5">{{ $ticket->number }}</div>
                            </td>
                            <td><span class="ppill {{ $stage['tone'] }}">{{ $stage['label'] }}</span></td>
                            <td class="small">{{ $counts['sent'] }} of {{ $counts['total'] }} uploaded</td>
                            <td class="text-end fw-semibold p-svc-name">{{ $money($ticket->total) }}</td>
                            <td><span class="ppill {{ $stage['paid'] ? 'lightgreen' : 'red' }}">{{ $stage['paid'] ? 'Paid' : 'Not paid' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('customer.tickets.show', $ticket) }}" class="tm-icon-btn-sm" title="View" aria-label="View service"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-md-none">
            @foreach ($services as $ticket)
                @php $stage = $portal->stage($ticket); @endphp
                <a href="{{ route('customer.tickets.show', $ticket) }}" class="p-svc-row">
                    <div>
                        <div class="nm">{{ $ticket->service->name }}</div>
                        <span class="ppill {{ $stage['tone'] }}">{{ $stage['label'] }}</span>
                    </div>
                    <div class="right">
                        <div class="fee">{{ $money($ticket->total) }}</div>
                        <span class="ppill {{ $stage['paid'] ? 'lightgreen' : 'red' }}">{{ $stage['paid'] ? 'Paid' : 'Not paid' }}</span>
                    </div>
                    <x-portal-icon name="chevron-right" :size="18" style="color: #a2a9b8;" />
                </a>
            @endforeach
        </div>
    @endif
</div>
