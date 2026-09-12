<?php

namespace Database\Factories\Features\Unit;

use App\Features\Unit\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('UNT-???')),
            'name' => $this->faker->word,
            'symbol' => strtoupper($this->faker->lexify('?')),
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }
}
