<?php

namespace Database\Factories\Features\Category;

use App\Features\Category\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('CAT-???')),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }
}
