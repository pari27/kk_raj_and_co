<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * SuperAdmin and Admin share the same access to the Services master; Employees never
     * reach this screen (see roles-permissions.md).
     */
    private function isAdminLike(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdminLike($user);
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
