<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private const LIMIT = 15;

    /**
     * Search the top-bar box: services, enquiries and clients, limited to what the user may see.
     */
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $user = $request->user();

        $tickets = collect();
        $enquiries = collect();
        $customers = collect();

        if (mb_strlen($term) >= 2) {
            $like = '%'.addcslashes($term, '\\%_').'%';

            $tickets = Ticket::query()
                ->visibleTo($user)
                ->with(['customer', 'service'])
                ->where(fn ($query) => $query
                    ->where('number', 'like', $like)
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like))
                    ->orWhereHas('service', fn ($service) => $service->where('name', 'like', $like)))
                ->latest('created_at')
                ->limit(self::LIMIT)
                ->get();

            $enquiries = Enquiry::query()
                ->visibleTo($user)
                ->with('customer')
                ->where(fn ($query) => $query
                    ->where('number', 'like', $like)
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like)))
                ->latest('created_at')
                ->limit(self::LIMIT)
                ->get();

            $customers = Customer::query()
                ->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get();
        }

        return view('search.index', compact('term', 'tickets', 'enquiries', 'customers'));
    }
}
