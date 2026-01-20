<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class ApiLoginTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create field_employee role with mobile app access enabled
        Role::create([
            'name' => 'field_employee',
            'guard_name' => 'web',
            'is_mobile_app_access_enabled' => true,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()
            ->withPassword('password123')
            ->create();

        $user->assignRole('field_employee');

        $response = $this->postJson('/api/V1/login', [
            'employeeId' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'statusCode',
                'status',
                'data' => [
                    'id',
                    'firstName',
                    'lastName',
                    'email',
                    'token',
                    'expiresIn',
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()
            ->withPassword('password123')
            ->create();

        $user->assignRole('field_employee');

        $response = $this->postJson('/api/V1/login', [
            'employeeId' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'failed',
            ]);
    }

    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/V1/login', [
            'employeeId' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // LoginRequest validates email exists - returns 400 for validation failure
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'failed',
            ]);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/V1/login', []);

        // API custom validation returns 400 for validation errors
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'failed',
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()
            ->withPassword('password123')
            ->create();

        $user->assignRole('field_employee');

        // First login to get token
        $loginResponse = $this->postJson('/api/V1/login', [
            'employeeId' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        // Now logout using the token
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/V1/auth/logout');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_user_without_mobile_access_cannot_login(): void
    {
        // Create a role without mobile app access
        Role::create([
            'name' => 'web_only_role',
            'guard_name' => 'web',
            'is_mobile_app_access_enabled' => false,
        ]);

        $user = User::factory()
            ->withPassword('password123')
            ->create();

        $user->assignRole('web_only_role');

        $response = $this->postJson('/api/V1/login', [
            'employeeId' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'failed',
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()
            ->inactive()
            ->withPassword('password123')
            ->create();

        $user->assignRole('field_employee');

        $response = $this->postJson('/api/V1/login', [
            'employeeId' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'failed',
            ]);
    }
}
