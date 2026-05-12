<?php

namespace Database\Factories;

use App\Models\Resident;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resident>
 */
class ResidentFactory extends Factory
{
    protected $model = Resident::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'resident_type' => 'tenant',
            'status' => 'active',
            'is_primary_owner' => false,
            'phone' => fake()->phoneNumber(),
            'secondary_phone' => null,
            'email' => fake()->safeEmail(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'notes' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => [
            'resident_type' => 'owner',
            'is_primary_owner' => true,
        ]);
    }
}
