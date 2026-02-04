<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => Gender::MALE,
            'dob' => fake()->date(),
            'phone' => fake()->phoneNumber(),
            'status' => UserAccountStatus::ACTIVE,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('123456'),
            'remember_token' => Str::random(10),
            'code' => 'EMP'.fake()->unique()->numerify('####'),
            'date_of_joining' => now()->subMonths(rand(1, 24)),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    /**
     * Create an HR user.
     */
    public function hr(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('hr');
        });
    }

    /**
     * Create a manager user.
     */
    public function manager(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('manager');
        });
    }

    /**
     * Create a field employee user.
     */
    public function fieldEmployee(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('field_employee');
        });
    }

    /**
     * Create a user with inactive status.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserAccountStatus::INACTIVE,
        ]);
    }

    /**
     * Create a user with a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }
}
