<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * SuperAdmin and Admin share the same access to the Employees master; Employees
     * never reach this screen (see roles-permissions.md).
     */
    private function isAdminLike(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function viewAnyEmployees(User $user): bool
    {
        return $this->isAdminLike($user);
    }

    public function viewEmployee(User $user, User $employee): bool
    {
        return $this->isAdminLike($user);
    }

    public function createEmployee(User $user): bool
    {
        return $this->isAdminLike($user);
    }

    public function updateEmployee(User $user, User $employee): bool
    {
        return $this->isAdminLike($user);
    }
}
