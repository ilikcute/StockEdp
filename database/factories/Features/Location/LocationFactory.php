<?php

namespace Database\Factories\Features\Location;

use App\Features\Location\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city.' Warehouse',
            'code' => strtoupper($this->faker->lexify('LOC-???')),
            'is_active' => true,
        ];
    }
}
