<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'catalog_id' => 'required|exists:software_catalogs,id',
            'quota_limit' => 'required|integer|min:1',
            'expiry_date' => 'nullable|date',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notes' => 'nullable|string',
            'price_per_unit' => 'nullable|numeric|min:0',
            'purchase_order_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'license_key' => 'nullable|string',
        ];
    }
}
