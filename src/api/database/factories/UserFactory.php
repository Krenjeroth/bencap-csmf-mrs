<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Test-only password for factory users; meets the password policy. */
    public const PASSWORD = 'Test-Password-2026!';

    protected static ?string $passwordHash = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$passwordHash ??= Hash::make(self::PASSWORD),
            'must_change_password' => false,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }

    /** A confirmed TOTP secret, so the user counts as having two-factor on. */
    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /** Attach a role by title (the role must already exist, e.g. via seeders). */
    public function withRole(string $title): static
    {
        return $this->afterCreating(function (User $user) use ($title) {
            $user->roles()->attach(Role::where('title', $title)->firstOrFail());
        });
    }

    /** System Administrator with two-factor enabled, ready for admin routes. */
    public function systemAdministrator(): static
    {
        return $this->withTwoFactor()->withRole(Role::SYSTEM_ADMINISTRATOR);
    }

    public function admin(): static
    {
        return $this->withRole(Role::ADMIN);
    }
}
