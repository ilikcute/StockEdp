<?php

namespace Database\Factories;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Location\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'transfer_number' => 'TRF-202607-'.$this->faker->unique()->numerify('####'),
            'origin_location_id' => Location::factory(),
            'destination_location_id' => Location::factory(),
            'status' => TransferStatus::DRAFT,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => $this->faker->sentence,
            'created_by' => User::factory(),
        ];
    }
}
