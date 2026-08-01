<?php

namespace Database\Factories\Features\Product;

use App\Features\Product\Models\Product;
use Database\Factories\Features\Category\CategoryFactory;
use Database\Factories\Features\Unit\UnitFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->lexify('PRD-???')),
            'category_id' => CategoryFactory::new(),
            'unit_id' => UnitFactory::new(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
