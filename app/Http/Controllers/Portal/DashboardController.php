<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientNotification;
use App\Services\ClientPortalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $services = $this->portal->services($customer);
        $fees = $this->portal->fees($services);
        return view('customer.dashboard', [
            'customer' => $customer,
            'services' => $services,
            'stats' => $this->portal->stats($services, $fees),
            'attention' => $this->portal->attentionItems($services, $fees),
            'recentUpdates' => ClientNotification::query()->where('customer_id', $customer->id)->latest('created_at')->latest('id')->limit(5)->get(),
            'portal' => $this->portal,
        ]);
    }
}
