<?php

namespace App\Features\MasterDataImport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitMasterDataImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:csv,txt',
            ],
            'expected_sha256' => [
                'required',
                'string',
                'size:64',
                'regex:/^[a-fA-F0-9]{64}$/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File CSV wajib diunggah.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.max' => 'Ukuran file CSV maksimal 5 MB.',
            'file.mimes' => 'Format file harus berupa file CSV (.csv).',
            'expected_sha256.required' => 'Checksum SHA256 validasi awal wajib disertakan.',
            'expected_sha256.size' => 'Checksum SHA256 harus 64 karakter.',
            'expected_sha256.regex' => 'Format checksum SHA256 tidak valid.',
        ];
    }
}
