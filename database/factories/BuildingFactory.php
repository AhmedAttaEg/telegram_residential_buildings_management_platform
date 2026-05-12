<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->streetName().' Building';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'code' => strtoupper(fake()->bothify('BLD-###')),
            'status' => 'active',
            'country' => 'Egypt',
            'city' => fake()->city(),
            'area' => fake()->streetName(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'postal_code' => fake()->postcode(),
        ];
    }
}
