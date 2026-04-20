<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'catalog_id' => 'required|exists:software_catalogs,id',
            'purchase_order_number' => 'nullable|string|max:255',
            'quota_limit' => 'required|integer|min:1',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'price_per_unit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'proof_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'license_key' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'proof_image.required' => 'Bukti pembelian berupa gambar wajib diunggah.',
            'catalog_id.required' => 'Silakan pilih software terlebih dahulu.',
            'quota_limit.min' => 'Kuota lisensi minimal adalah 1.',
        ];
    }
}
