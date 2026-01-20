<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class PermissionTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create essential roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'hr', 'guard_name' => 'web']);
        Role::create(['name' => 'manager', 'guard_name' => 'web']);
        Role::create(['name' => 'field_employee', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true]);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->get('/dashboard');

        // App redirects to /auth/login
        $response->assertRedirect('/auth/login');
    }

    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = $this->createAdmin();

        // Test that authenticated user doesn't get redirected to login
        $response = $this->actingAs($user)->get('/dashboard');

        // Should not redirect to auth/login (may redirect elsewhere or load)
        $this->assertNotEquals('/auth/login', $response->headers->get('Location'));
    }

    public function test_user_has_correct_role_assigned(): void
    {
        $admin = $this->createAdmin();
        $hr = $this->createHrUser();
        $manager = $this->createManager();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($hr->hasRole('hr'));
        $this->assertTrue($manager->hasRole('manager'));
    }

    public function test_user_factory_creates_user_with_role(): void
    {
        $fieldEmployee = $this->createFieldEmployee();

        $this->assertTrue($fieldEmployee->hasRole('field_employee'));
        $this->assertFalse($fieldEmployee->hasRole('admin'));
    }

    public function test_user_without_role_cannot_be_assigned_nonexistent_role(): void
    {
        $this->expectException(\Spatie\Permission\Exceptions\RoleDoesNotExist::class);

        $user = $this->createUser();
        $user->assignRole('nonexistent_role');
    }
}
