<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function update(User $user, Payment $payment): bool
    {
        return true;
    }

    /**
     * Deleting a recorded payment is an Admin/SuperAdmin-only action.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
