<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EnquiryStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $fyStart = now()->month >= 4
            ? Carbon::create(now()->year, 4, 1)->startOfDay()
            : Carbon::create(now()->year - 1, 4, 1)->startOfDay();

        $receivedThisMonth = Payment::query()->where('paid_at', '>=', $monthStart)->get();
        $receivedThisFy = Payment::query()->where('paid_at', '>=', $fyStart)->sum('amount');

        $pendingEnquiries = Enquiry::query()
            ->with(['customer', 'tickets.service', 'payments'])
            ->get()
            ->filter(fn (Enquiry $enquiry) => $enquiry->balanceDue() > 0)
            ->sortByDesc(fn (Enquiry $enquiry) => $enquiry->balanceDue())
            ->values();

        $partialPaymentsCount = $pendingEnquiries->filter(fn (Enquiry $enquiry) => $enquiry->amountPaid() > 0)->count();

        $payments = Payment::query()
            ->with(['enquiry.customer', 'enquiry.tickets.service', 'receivedBy'])
            ->latest('paid_at')
            ->latest('id')
            ->get();

        $modeBreakdown = $receivedThisMonth
            ->groupBy('mode')
            ->map(fn ($group) => $group->sum('amount'))
            ->sortDesc();

        $modeTotal = $modeBreakdown->sum() ?: 1;

        $receivedByUsers = User::query()->orderBy('name')->get(['id', 'name']);

        return view('payments.index', compact(
            'receivedThisMonth',
            'receivedThisFy',
            'pendingEnquiries',
            'partialPaymentsCount',
            'payments',
            'modeBreakdown',
            'modeTotal',
            'receivedByUsers',
        ));
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $enquiry = Enquiry::query()->with('payments')->findOrFail($request->validated('enquiry_id'));

        $balanceDue = $enquiry->balanceDue();
        $amount = (float) $request->validated('amount');

        abort_if($balanceDue <= 0, 422, 'This enquiry has no balance due.');
        abort_if($amount > $balanceDue, 422, "Amount can't exceed the balance due.");

        $isPartial = $amount < $balanceDue;

        $payment = Payment::create([
            'enquiry_id' => $enquiry->id,
            'amount' => $amount,
            'mode' => $request->validated('mode'),
            'reference' => $request->validated('reference'),
            'note' => $request->validated('note'),
            'received_by' => Auth::id(),
            'paid_at' => $request->validated('paid_at'),
            'is_partial' => $isPartial,
        ]);

        AuditLogger::log(
            action: 'Created',
            module: 'Payment',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: '₹'.number_format($amount).' received via '.$payment->mode.' for '.$enquiry->number.($isPartial ? ' (partial)' : ''),
            subject: $enquiry,
        );

        EnquiryStatusService::recompute($enquiry);

        if ($request->boolean('return_to_enquiry')) {
            return redirect()
                ->route('enquiries.show', $enquiry)
                ->with('status', "Payment recorded for {$enquiry->number}.");
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Payment of ₹'.number_format($amount)." recorded for {$enquiry->number}.");
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $enquiry = $payment->enquiry()->with('payments')->firstOrFail();

        $otherPaid = (float) $enquiry->payments->where('id', '!=', $payment->id)->sum('amount');
        $balanceExcludingThis = (float) $enquiry->total - $otherPaid;

        $amount = (float) $request->validated('amount');

        abort_if($amount > $balanceExcludingThis, 422, "Amount can't exceed the balance due.");

        $payment->update([
            'amount' => $amount,
            'mode' => $request->validated('mode'),
            'reference' => $request->validated('reference'),
            'note' => $request->validated('note'),
            'paid_at' => $request->validated('paid_at'),
            'is_partial' => $amount < $balanceExcludingThis,
        ]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Payment',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: 'Payment updated to ₹'.number_format($amount).' via '.$payment->mode.' for '.$enquiry->number,
            subject: $enquiry,
        );

        EnquiryStatusService::recompute($enquiry);

        return redirect()
            ->route('payments.index')
            ->with('status', "Payment for {$enquiry->number} was updated.");
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $enquiry = $payment->enquiry()->with('payments')->firstOrFail();
        $amount = (float) $payment->amount;
        $mode = $payment->mode;

        $payment->delete();

        AuditLogger::log(
            action: 'Deleted',
            module: 'Payment',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: 'Deleted a ₹'.number_format($amount).' '.$mode.' payment for '.$enquiry->number,
            subject: $enquiry,
        );

        EnquiryStatusService::recompute($enquiry);

        return redirect()
            ->route('payments.index')
            ->with('status', "Payment for {$enquiry->number} was deleted.");
    }

    public function export(): StreamedResponse
    {
        $payments = Payment::query()
            ->with(['enquiry.customer', 'enquiry.tickets.service', 'receivedBy'])
            ->latest('paid_at')
            ->latest('id')
            ->get();

        $filename = 'payments-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Client', 'Enquiry', 'Services', 'Amount', 'Mode', 'Reference', 'Received by', 'Payment']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    '="'.$payment->paid_at->format('Y-m-d').'"',
                    $payment->enquiry->customer->name ?? '—',
                    $payment->enquiry->number ?? '—',
                    $payment->enquiry->tickets->pluck('service.name')->filter()->implode(', ') ?: '—',
                    number_format((float) $payment->amount, 2),
                    $payment->mode,
                    $payment->reference,
                    $payment->receivedBy->name ?? '—',
                    $payment->is_partial ? 'Partial' : 'Full',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
