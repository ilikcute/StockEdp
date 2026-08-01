<?php

namespace App\Features\Inventory\Actions;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Models\StockIssue;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class CancelStockIssueAction
{
    public function execute(StockIssue $issue, ?int $userId = null): StockIssue
    {
        return DB::transaction(function () use ($issue, $userId) {
            $lockedIssue = clone $issue;
            $lockedIssue = StockIssue::where('id', $lockedIssue->id)->lockForUpdate()->first();

            if (! $lockedIssue->isDraft()) {
                throw new DomainException('Only DRAFT issues can be canceled.', 409);
            }

            // Check authorization
            $locationIds = $lockedIssue->items->pluck('location_id')->unique()->toArray();
            if ($userId) {
                $user = User::find($userId);
                if ($user && count(array_diff($locationIds, $user->getAllowedLocationIds())) > 0) {
                    throw new DomainException('User is not authorized for one or more locations in this document.', 403);
                }
            }

            $lockedIssue->update([
                'status' => IssueStatus::CANCELED,
            ]);

            return $lockedIssue;
        });
    }
}
