{{-- The "My services" card: a table on a computer, a list on a phone. Needs $services and $portal. --}}
@php $money = fn ($amount) => '₹'.number_format((float) $amount); @endphp

<div class="p-card">
    <div class="p-card-head navy">
        <span>My services</span>
        <span class="meta">{{ $services->count() }} {{ $services->count() === 1 ? 'service' : 'services' }}</span>
    </div>

    @if ($services->isEmpty())
        <div class="p-card-body text-center tm-muted" style="font-size: .85rem;">You have no services with us yet.</div>
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
                                <div class="fw-semibold">{{ $ticket->service->name }}</div>
                                <div class="tm-muted" style="font-size: .75rem;">{{ $ticket->number }}</div>
                            </td>
                            <td><span class="ppill {{ $stage['tone'] }}">{{ $stage['label'] }}</span></td>
                            <td class="small">{{ $counts['sent'] }} of {{ $counts['total'] }} uploaded</td>
                            <td class="text-end fw-semibold">{{ $money($ticket->total) }}</td>
                            <td><span class="ppill {{ $stage['paid'] ? 'lightgreen' : 'red' }}">{{ $stage['paid'] ? 'Paid' : 'Not paid' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('customer.tickets.show', $ticket) }}" class="btn btn-sm" style="background: #e0ebff; color: #1d4fb8; font-weight: 700; font-size: .78rem;">Open</a>
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
