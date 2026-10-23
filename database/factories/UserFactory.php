<?php

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 *
 * Matches the real users table (user_id, role_id, username, full_name, is_active).
 * Requires the roles table to be seeded (Database\Seeders\RoleSeeder).
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
            'role_id' => Role::ANALIS,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'full_name' => fake()->name(),
            'is_active' => true,
        ];
    }

    public function role(int $roleId): static
    {
        return $this->state(fn () => ['role_id' => $roleId]);
    }

    public function admin(): static
    {
        return $this->role(Role::ADMIN);
    }

    public function analis(): static
    {
        return $this->role(Role::ANALIS);
    }

    public function kepalaDivisi(): static
    {
        return $this->role(Role::KEPALA_DIVISI);
    }

    public function kepalaUpa(): static
    {
        return $this->role(Role::KEPALA_UPA);
    }
}
