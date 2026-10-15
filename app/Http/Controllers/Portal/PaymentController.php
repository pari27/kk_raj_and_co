<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ClientPortalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $services = $this->portal->services($customer);

        $owing = $services
            ->map(fn ($ticket) => ['name' => $ticket->service->name, 'balance' => $this->portal->feeShare($ticket)['balance']])
            ->filter(fn (array $row) => $row['balance'] > 0)
            ->values();

        // A short code the client puts in the payment note so staff can match the payment.
        $reference = strtoupper(Str::of($customer->name)->before(' ')->ascii()->replaceMatches('/[^A-Za-z0-9]/', '')->toString())
            .'-'.($customer->phone ? substr(preg_replace('/\D/', '', $customer->phone), -5) : $customer->id);

        return view('customer.payments', [
            'payments' => $this->portal->payments($customer),
            'fees' => $this->portal->fees($services),
            'owing' => $owing,
            'reference' => $reference,
        ]);
    }
}
