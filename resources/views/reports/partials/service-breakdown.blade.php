<section class="report-card mb-3">
    <div class="report-card-title d-flex justify-content-between"><span>Service-wise breakdown</span><span class="small fw-normal">{{ $filters['from'] ?? 'All dates' }} {{ isset($filters['to']) ? '– '.$filters['to'] : '' }}</span></div>
    <div class="table-responsive"><table class="table report-table"><thead><tr><th>Service</th><th class="text-end">Tickets</th><th class="text-end">Billed</th><th class="text-end">Received</th><th class="text-end">Pending</th><th class="text-end">GST</th></tr></thead><tbody>
        @forelse ($serviceBreakdown as $row)<tr><td class="fw-bold">{{ $row['service'] }}</td><td class="text-end">{{ $row['tickets'] }}</td><td class="text-end">{{ $money($row['billed']) }}</td><td class="text-end text-success fw-bold">{{ $money($row['received']) }}</td><td class="text-end text-danger fw-bold">{{ $money($row['pending']) }}</td><td class="text-end">{{ $money($row['gst']) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-secondary p-4">No service activity in this period.</td></tr>@endforelse
    </tbody></table></div>
</section>
