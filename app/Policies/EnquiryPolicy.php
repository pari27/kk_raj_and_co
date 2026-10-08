<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\User;

class EnquiryPolicy
{
    /**
     * Creating enquiries is a shared staff capability. Everyone can access
     * the Enquiries list, but an Employee's own list/detail access is scoped
     * to enquiries with at least one ticket assigned to them (see view()).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admin/SuperAdmin can view any enquiry. An Employee can only view an
     * enquiry that has at least one ticket assigned to them.
     */
    public function view(User $user, Enquiry $enquiry): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        return $enquiry->tickets()->where('assigned_to', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Setting a discount or overriding a line's price is an Admin/SuperAdmin-only action.
     */
    public function managePricing(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Everyone who can create an enquiry can also choose who each ticket is
     * assigned to. An Employee's tickets default to themselves but they can
     * delegate a line to a colleague; Admin/SuperAdmin default to unassigned.
     */
    public function assignTickets(User $user): bool
    {
        return true;
    }
}
