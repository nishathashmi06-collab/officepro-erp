<?php

namespace Database\Factories;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => fn () => Role::where('slug', Permissions::ROLE_EMPLOYEE)->value('id'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function role(string $slug): static
    {
        return $this->state(fn () => ['role_id' => Role::where('slug', $slug)->value('id')]);
    }
}
