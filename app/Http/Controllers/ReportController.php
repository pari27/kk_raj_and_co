<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportService;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportFilterRequest $request): View
    {
        $filters = array_merge($this->defaultDates(), $request->validated());

        return view('reports.index', [
            ...$this->reports->data($filters, $request->user()),
            'filters' => $filters,
            'tab' => $filters['tab'] ?? 'revenue',
        ]);
    }

    /** @return array{from: string, to: string} */
    private function defaultDates(): array
    {
        $year = now()->month >= 4 ? now()->year : now()->year - 1;

        return ['from' => now()->setDate($year, 4, 1)->toDateString(), 'to' => now()->toDateString()];
    }

    public function export(ReportFilterRequest $request): StreamedResponse
    {
        $tickets = $this->reports->tickets(array_merge($this->defaultDates(), $request->validated()), $request->user());

        return response()->streamDownload(function () use ($tickets): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Ticket', 'Client', 'Service', 'Staff', 'Status', 'Created', 'Billed', 'Paid', 'Due']);

            foreach ($tickets as $ticket) {
                fputcsv($output, [
                    $ticket->number,
                    $ticket->customer?->name,
                    $ticket->service?->name,
                    $ticket->assignedTo?->name,
                    $ticket->status?->value,
                    $ticket->created_at?->format('Y-m-d'),
                    $ticket->total,
                    $ticket->amountPaid(),
                    $ticket->balanceDue(),
                ]);
            }

            fclose($output);
        }, 'reports.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
