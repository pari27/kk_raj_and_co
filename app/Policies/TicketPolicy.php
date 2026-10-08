<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    private function isAdminLike(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admins/SuperAdmins see every ticket; an Employee only sees tickets assigned to them.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $this->isAdminLike($user) || $ticket->assigned_to === $user->id;
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $this->isAdminLike($user) || $ticket->assigned_to === $user->id;
    }

    /**
     * Admins/SuperAdmins and the assigned employee can upload documents on a ticket.
     */
    public function uploadDocument(User $user, Ticket $ticket): bool
    {
        return $this->isAdminLike($user) || $ticket->assigned_to === $user->id;
    }

    /**
     * Admins/SuperAdmins and the assigned employee can verify/reject uploaded documents.
     */
    public function verifyDocument(User $user, Ticket $ticket): bool
    {
        return $this->isAdminLike($user) || $ticket->assigned_to === $user->id;
    }

    /**
     * Reassigning a ticket to a different employee is an Admin/SuperAdmin-only action.
     */
    public function reassign(User $user): bool
    {
        return $this->isAdminLike($user);
    }
}
