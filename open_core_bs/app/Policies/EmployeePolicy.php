<?php

namespace App\Policies;

use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'hr']);
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, User $employee): bool
    {
        // Admin and HR can view any employee
        if ($user->hasAnyRole(['admin', 'hr'])) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $employee->id;
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'hr']);
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $user, User $employee): bool
    {
        // Admin and HR can update any employee (except they can't update their own work/compensation info through this)
        if ($user->hasAnyRole(['admin', 'hr'])) {
            return true;
        }

        // Users can update their own basic info only
        return $user->id === $employee->id;
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $user, User $employee): bool
    {
        // Admin and HR can delete employees
        if (! $user->hasAnyRole(['admin', 'hr'])) {
            return false;
        }

        // Cannot delete yourself
        return $user->id !== $employee->id;
    }

    /**
     * Determine whether the user can terminate the employee.
     */
    public function terminate(User $user, User $employee): bool
    {
        // Admin and HR can terminate employees
        if (! $user->hasAnyRole(['admin', 'hr'])) {
            return false;
        }

        // Cannot terminate yourself
        return $user->id !== $employee->id;
    }

    /**
     * Determine whether the user can manage probation for the employee.
     */
    public function manageProbation(User $user, User $employee): bool
    {
        return $user->hasAnyRole(['admin', 'hr']);
    }

    /**
     * Determine whether the user can view the employee's timeline.
     */
    public function viewTimeline(User $user, User $employee): bool
    {
        // Admin and HR can view any employee's timeline
        if ($user->hasAnyRole(['admin', 'hr'])) {
            return true;
        }

        // Users can view their own timeline
        return $user->id === $employee->id;
    }

    /**
     * Determine whether the user can restore the employee.
     */
    public function restore(User $user, User $employee): bool
    {
        return $user->hasAnyRole(['admin', 'hr']);
    }

    /**
     * Determine whether the user can permanently delete the employee.
     */
    public function forceDelete(User $user, User $employee): bool
    {
        return $user->hasRole('admin');
    }
}
