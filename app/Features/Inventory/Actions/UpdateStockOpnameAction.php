<?php

namespace App\Features\Inventory\Actions;

use App\Features\Inventory\Models\StockOpname;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class UpdateStockOpnameAction
{
    public function execute(StockOpname $opname, array $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $userId) {
            $lockedOpname = StockOpname::where('id', $opname->id)->lockForUpdate()->first();

            if (! $lockedOpname->isDraft()) {
                throw new DomainException('Hanya dokumen berstatus DRAFT yang dapat diperbarui.', 409);
            }

            if (isset($data['opname_date']) && $data['opname_date'] > now()->format('Y-m-d')) {
                throw new DomainException('Tanggal stock opname tidak boleh melebihi tanggal hari ini.', 422);
            }

            $lockedOpname->update([
                'opname_date' => $data['opname_date'] ?? $lockedOpname->opname_date,
                'notes' => $data['notes'] ?? $lockedOpname->notes,
                'updated_by' => $userId,
            ]);

            return $lockedOpname->fresh(['location', 'creator', 'updater']);
        });
    }
}
