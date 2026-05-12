<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'status' => 'active',
            'subscription_status' => 'trial',
            'subscription_plan' => 'monthly',
            'trial_ends_at' => Carbon::now()->addDays(14),
            'grace_ends_at' => null,
            'subscription_ends_at' => null,
            'suspended_at' => null,
            'feature_flags' => config('tenant.features'),
            'brand_name' => $name,
            'logo_path' => null,
            'primary_color' => '#0f766e',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'subscription_status' => 'expired',
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => now()->subDay(),
        ]);
    }

    public function withFeature(string $feature, bool $enabled): static
    {
        return $this->state(fn (array $attributes) => [
            'feature_flags' => array_replace($attributes['feature_flags'] ?? config('tenant.features', []), [
                $feature => $enabled,
            ]),
        ]);
    }
}
