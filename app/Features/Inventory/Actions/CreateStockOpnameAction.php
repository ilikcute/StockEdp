<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Repositories\Contracts\StockOpnameRepositoryInterface;
use App\Features\Location\Models\Location;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateStockOpnameAction
{
    public function __construct(
        private readonly StockOpnameRepositoryInterface $repository
    ) {}

    public function execute(array $data, int $userId): StockOpname
    {
        $location = Location::find($data['location_id']);

        if (! $location || ! $location->is_active) {
            throw new DomainException('Lokasi persediaan tidak aktif.', 422);
        }

        if ($data['opname_date'] > now()->format('Y-m-d')) {
            throw new DomainException('Tanggal stock opname tidak boleh melebihi tanggal hari ini.', 422);
        }

        $attempts = 0;
        $maxAttempts = 5;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return DB::transaction(function () use ($data, $userId) {
                    $opnameNumber = $this->repository->generateOpnameNumber();

                    return StockOpname::create([
                        'opname_number' => $opnameNumber,
                        'location_id' => $data['location_id'],
                        'opname_date' => $data['opname_date'],
                        'status' => OpnameStatus::DRAFT,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => $userId,
                    ]);
                });
            } catch (QueryException $e) {
                if ($e->getCode() === '23000' && str_contains($e->getMessage(), '1062')) {
                    if ($attempts >= $maxAttempts) {
                        throw new DomainException('Gagal membuat nomor stock opname. Silakan coba lagi.', 409);
                    }
                    usleep(50000);

                    continue;
                }
                throw $e;
            }
        }

        throw new DomainException('Gagal membuat draft stock opname.', 500);
    }
}
