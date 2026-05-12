<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartment>
 */
class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Apartment $apartment): void {
            if ($apartment->building && $apartment->tenant_id !== $apartment->building->tenant_id) {
                $apartment->tenant_id = $apartment->building->tenant_id;
            }
        })->afterCreating(function (Apartment $apartment): void {
            if ($apartment->tenant_id !== $apartment->building->tenant_id) {
                $apartment->forceFill([
                    'tenant_id' => $apartment->building->tenant_id,
                ])->save();
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'building_id' => Building::factory(),
            'unit_number' => (string) fake()->unique()->numberBetween(1, 500),
            'occupancy_status' => 'vacant',
            'status' => 'active',
            'floor_number' => fake()->numberBetween(0, 20),
            'unit_type' => fake()->randomElement(['studio', 'flat', 'duplex']),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 4),
            'area_value' => fake()->randomFloat(2, 50, 300),
            'area_unit' => 'sqm',
        ];
    }

    public function forBuilding(Building $building): static
    {
        return $this->state(fn () => [
            'tenant_id' => $building->tenant_id,
            'building_id' => $building->id,
        ]);
    }

    public function occupied(): static
    {
        return $this->state(fn () => [
            'occupancy_status' => 'occupied',
        ]);
    }
}
