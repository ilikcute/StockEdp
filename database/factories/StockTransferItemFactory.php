<?php

namespace Database\Factories;

use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferItemFactory extends Factory
{
    protected $model = StockTransferItem::class;

    public function definition(): array
    {
        return [
            'stock_transfer_id' => StockTransfer::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
