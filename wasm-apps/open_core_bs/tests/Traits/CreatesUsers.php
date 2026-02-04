<?php

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Helper trait for creating test users with various roles.
 */
trait CreatesUsers
{
    /**
     * Create and return an admin user.
     */
    protected function createAdmin(array $attributes = []): User
    {
        $this->ensureRoleExists('admin');

        return User::factory()->admin()->create($attributes);
    }

    /**
     * Create and return an HR user.
     */
    protected function createHrUser(array $attributes = []): User
    {
        $this->ensureRoleExists('hr');

        return User::factory()->hr()->create($attributes);
    }

    /**
     * Create and return a manager user.
     */
    protected function createManager(array $attributes = []): User
    {
        $this->ensureRoleExists('manager');

        return User::factory()->manager()->create($attributes);
    }

    /**
     * Create and return a field employee user.
     */
    protected function createFieldEmployee(array $attributes = []): User
    {
        $this->ensureRoleExists('field_employee');

        return User::factory()->fieldEmployee()->create($attributes);
    }

    /**
     * Create a regular user without any role.
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Act as an admin user for the current test.
     */
    protected function actingAsAdmin(array $attributes = []): self
    {
        return $this->actingAs($this->createAdmin($attributes));
    }

    /**
     * Act as an HR user for the current test.
     */
    protected function actingAsHr(array $attributes = []): self
    {
        return $this->actingAs($this->createHrUser($attributes));
    }

    /**
     * Act as a manager user for the current test.
     */
    protected function actingAsManager(array $attributes = []): self
    {
        return $this->actingAs($this->createManager($attributes));
    }

    /**
     * Act as a field employee user for the current test.
     */
    protected function actingAsFieldEmployee(array $attributes = []): self
    {
        return $this->actingAs($this->createFieldEmployee($attributes));
    }

    /**
     * Act as the given user for API requests.
     */
    protected function actingAsApiUser(User $user, string $guard = 'api'): self
    {
        return $this->actingAs($user, $guard);
    }

    /**
     * Ensure a role exists in the database.
     */
    protected function ensureRoleExists(string $roleName): void
    {
        if (! Role::where('name', $roleName)->exists()) {
            Role::create(['name' => $roleName, 'guard_name' => 'web']);
        }
    }
}
