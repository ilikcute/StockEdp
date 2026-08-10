<?php

namespace App\Features\MasterDataImport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateMasterDataImportRequest extends FormRequest
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
                'max:5120', // 5 MB in kilobytes
                'mimes:csv,txt',
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
        ];
    }
}
