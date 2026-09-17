<?php

namespace App\Features\Audit\Services;

use App\Features\Audit\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * Mencatat aktivitas sensitif ke dalam tabel activity_logs.
     *
     * @param string          $module      Nama modul/domain (mis: auth, month_end, store_allocations, users).
     * @param string          $action      Aksi yang dilakukan (mis: login, logout, close, reopen, create, update).
     * @param string|null     $description Deskripsi singkat dalam Bahasa Indonesia.
     * @param Model|null      $subject     Model entitas yang menjadi objek aksi (opsional).
     * @param array           $properties  Data tambahan terstruktur (JSON).
     * @param int|null        $userId      User pelaku; jika null, gunakan auth()->id().
     * @param Request|null    $request     Request HTTP; jika null, gunakan request() saat ini.
     */
    public function record(
        string $module,
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?int $userId = null,
        ?Request $request = null,
    ): ActivityLog {
        try {
            $request = $request ?? request();
            $userId = $userId ?? Auth::id();

            return ActivityLog::create([
                'user_id' => $userId,
                'module' => $module,
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Gagal mencatat ActivityLog [{$module}.{$action}]: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return new ActivityLog();
        }
    }

    /**
     * Mencatat aktivitas dengan subjek dan pelaku tertentu.
     */
    public function recordSubject(
        Model $subject,
        string $module,
        string $action,
        ?string $description = null,
        array $properties = [],
        ?int $userId = null,
    ): ActivityLog {
        return $this->record($module, $action, $description, $subject, $properties, $userId);
    }

    /**
     * Ambil user id pelaku (fallback ke auth login aktif).
     */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? Auth::id();
    }
}