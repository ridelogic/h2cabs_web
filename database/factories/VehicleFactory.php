<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * For automated tests only.
 *
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Test '.Str::title(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => 'sedan',
            'passenger_capacity' => null,
            'luggage_capacity' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
