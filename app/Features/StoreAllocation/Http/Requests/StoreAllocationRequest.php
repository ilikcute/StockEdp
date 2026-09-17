<?php

namespace App\Features\StoreAllocation\Http\Requests;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

class StoreAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $technicianUserId = $this->input('technician_user_id');

        // Jika technician_user_id tidak dikirim di request body, izinkan authorize lolos
        // agar validasi rules() yang menangani error 422 (field is required)
        if (empty($technicianUserId)) {
            return $user->can(PermissionCode::STORE_ALLOCATIONS_CREATE_OWN->value)
                || $user->can(PermissionCode::STORE_ALLOCATIONS_CREATE_FOR_OTHERS->value);
        }

        $isOwn = (int) $technicianUserId === (int) $user->id;

        if ($isOwn) {
            return $user->can(PermissionCode::STORE_ALLOCATIONS_CREATE_OWN->value);
        }

        return $user->can(PermissionCode::STORE_ALLOCATIONS_CREATE_FOR_OTHERS->value);
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'technician_user_id' => ['required', 'integer', 'exists:users,id'],
            'technician_location_id' => ['required', 'integer', 'exists:locations,id'],
            // Catatan: allocated_at bersifat opsional dan diabaikan oleh CreateStoreAllocationAction.
            // Tanggal pemasangan selalu ditentukan server (now()) untuk mencegah backdating ke
            // periode tutup buku yang dapat merusak data stok.
            'allocated_at' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.pulled_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.pulled_quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.pulled_serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.defective_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
