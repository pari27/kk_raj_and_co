<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Admins manage the Services master. Employees may only see the
     * list (viewAny); they cannot open, add, edit or activate/deactivate a service.
     */
    private function isAdminLike(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return $this->isAdminLike($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdminLike($user);
    }

    public function update(User $user, Service $service): bool
    {
        return $this->isAdminLike($user);
    }
}
