<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportService;
use App\Support\XlsxWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public const PER_PAGE = 25;

    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportFilterRequest $request): View
    {
        [$report, $filters] = $this->resolve($request);

        $result = $this->reports->run($report, $filters, $request->user());
        $paged = in_array($report, ReportService::PAGED, true);

        $rows = $result['rows'];

        if ($paged) {
            $page = max(1, (int) $request->query('page', 1));
            $rows = (new LengthAwarePaginator(
                $result['rows']->forPage($page, self::PER_PAGE)->values(),
                $result['rows']->count(),
                self::PER_PAGE,
                $page,
                ['path' => route('reports.index'), 'query' => $request->except('page')],
            ));
        }

        return view('reports.index', [
            'report' => $report,
            'filters' => $filters,
            'result' => $result,
            'rows' => $rows,
            'paged' => $paged,
            'options' => $this->reports->options($request->user()),
            'definitions' => ReportService::REPORTS,
            'groups' => ReportService::GROUPS,
        ]);
    }

    /**
     * Download the report currently on screen (every row, not just the page) as an
     * .xlsx file. Dates and text are stored as text so Excel never shows "####".
     */
    public function export(ReportFilterRequest $request): BinaryFileResponse
    {
        [$report, $filters] = $this->resolve($request);

        $result = $this->reports->run($report, $filters, $request->user());
        $columns = $result['columns'];

        $value = function (array $column, mixed $cell): string|int|float|\DateTimeInterface|null {
            return match ($column['type']) {
                'link', 'chip' => (string) ($cell['text'] ?? ''),
                'days' => $cell['value'] ?? null,
                'money', 'money_green', 'money_red' => round((float) $cell, 2),
                default => $this->asExcelValue($cell),
            };
        };

        $sheet = new XlsxWriter(substr($result['title'], 0, 31));
        $sheet->moneyColumns(array_keys(array_filter($columns, fn (array $column) => str_starts_with($column['type'], 'money'))));

        // Client history carries the client's name in the title, e.g. "Client history (manoj)".
        $heading = $result['client'] ? $result['title'].' ('.$result['client']->name.')' : $result['title'];

        $sheet->addRow([$heading], 'title');
        $sheet->addRow(array_column($columns, 'label'), 'header');

        foreach ($result['rows'] as $row) {
            $sheet->addRow(array_map(fn (array $column) => $value($column, $row[$column['key']] ?? null), $columns));
        }

        if ($result['rows']->isNotEmpty()) {
            $sheet->addRow(array_map(function (array $column, int $index) use ($result, $value) {
                if ($index === 0) {
                    return $result['total_label'];
                }

                return array_key_exists($column['key'], $result['total']) ? $value($column, $result['total'][$column['key']]) : null;
            }, $columns, array_keys($columns)), 'total');
        }

        $filename = str_replace('_', '-', $report).'-'.now()->format('Y-m-d').'.xlsx';

        return response()->download($sheet->save(), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Dates shown on screen as "12 Oct 2026" become real Excel dates (shown dd-mm-yyyy);
     * numbers stay numbers and everything else is text.
     */
    private function asExcelValue(mixed $cell): string|int|float|\DateTimeInterface
    {
        if (is_int($cell) || is_float($cell)) {
            return $cell;
        }

        $text = (string) ($cell ?? '');

        if (preg_match('/^\d{1,2} [A-Z][a-z]{2} \d{4}$/', $text) === 1) {
            $date = \DateTimeImmutable::createFromFormat('j M Y', $text);

            if ($date !== false) {
                return $date;
            }
        }

        return $text;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resolve(ReportFilterRequest $request): array
    {
        $report = $request->validated('report') ?? 'fees_collected';
        $filters = array_merge($this->reports->defaults($report), array_filter(
            $request->validated(),
            fn ($value) => $value !== null && $value !== '',
        ));

        // Client history opens on the first client instead of an empty page.
        if ($report === 'client_history' && empty($filters['client_id'])) {
            $first = $this->reports->options($request->user())['clients']->first();

            if ($first) {
                $filters['client_id'] = $first->id;
            }
        }

        return [$report, $filters];
    }
}
