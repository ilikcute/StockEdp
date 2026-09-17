<?php

namespace App\Features\Rma\Actions;

use App\Features\Rma\Enums\RmaStatus;
use App\Features\Rma\Models\VendorRma;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CancelVendorRmaAction
{
    public function execute(VendorRma $rma, ?string $reason = null): VendorRma
    {
        return DB::transaction(function () use ($rma, $reason) {
            $lockedRma = VendorRma::where('id', $rma->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRma->isDraft()) {
                throw new DomainException("Hanya dokumen RMA berstatus DRAFT yang dapat dibatalkan.", 409);
            }

            $lockedRma->update([
                'status' => RmaStatus::CANCELLED,
                'notes' => trim(($lockedRma->notes ? $lockedRma->notes . "\n" : '') . 'Dibatalkan: ' . ($reason ?? 'Tidak ada keterangan.')),
            ]);

            return $lockedRma;
        });
    }
}
