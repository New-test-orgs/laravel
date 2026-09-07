<?php

namespace Database\Factories;

use App\Models\StoreMigration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreMigration>
 */
class StoreMigrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(10000, 99999),
            'source' => 'Magento 1.9',
            'target' => 'Shopify',
            'status' => 'active',
            'repository' => 'cart2cart-migration-scripts',
            'owner' => 'You',
            'initials' => 'YO',
            'avatar' => 'teal',
            'started_at' => now(),
        ];
    }
}
