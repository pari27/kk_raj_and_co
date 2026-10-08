<section class="report-card">
    <div class="report-card-title">Oldest open tickets</div>
    <div class="table-responsive"><table class="table report-table"><thead><tr><th>Ticket</th><th>Client</th><th>Staff</th><th>Status</th><th class="text-end">Open for</th></tr></thead><tbody>
        @forelse ($openTickets as $ticket)<tr><td><a href="{{ route('tickets.show', $ticket) }}" class="fw-bold">{{ $ticket->number }}</a><div class="text-secondary small">{{ $ticket->service?->name }}</div></td><td class="fw-semibold">{{ $ticket->customer?->name ?? '—' }}</td><td>{{ $ticket->assignedTo?->name ?? '—' }}</td><td><span class="report-badge" style="background:{{ ($statusColors[$ticket->status?->value] ?? '#eef0f3').'22' }};color:{{ $statusColors[$ticket->status?->value] ?? '#475366' }}">{{ $ticket->status?->value }}</span></td><td class="text-end fw-bold text-danger">{{ $ticket->created_at?->diffInDays(now()) ?? 0 }} days</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary p-4">No open tickets match these filters.</td></tr>@endforelse
    </tbody></table></div>
</section>
