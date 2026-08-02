<?php

namespace Database\Factories;

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
            'name'              => fake()->name(),
            'matric'            => 'SUMAS/' . strtoupper(fake()->lexify('??')) . '/' . fake()->year() . '/' . fake()->unique()->numerify('####'),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone'             => fake()->numerify('+234 80########'),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => 'student',
            'status'            => 'Pending',
            'dept'              => fake()->randomElement(['Medicine & Surgery', 'Nursing Science', 'Computer Science', 'Pharmacy', 'Medical Laboratory Science']),
            'level'             => fake()->randomElement(['100 Level', '200 Level', '300 Level', '400 Level', '500 Level']),
            'gender'            => fake()->randomElement(['Male', 'Female']),
            'verified'          => fake()->boolean(60),
            'docs_count'        => fake()->numberBetween(3, 7),
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
     * Indicate that the model is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'     => 'SUMAS Administrator',
            'username' => 'admin',
            'matric'   => null,
            'email'    => 'admin@sumas.edu.ng',
            'role'     => 'admin',
            'status'   => 'Approved',
            'verified' => true,
        ]);
    }
}
