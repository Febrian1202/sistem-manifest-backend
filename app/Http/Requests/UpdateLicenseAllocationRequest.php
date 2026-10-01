<?php

namespace App\Http\Requests;

use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseAllocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_inventory_id' => ['required', 'exists:license_inventories,id'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'allocated_quota' => ['required', 'integer', 'min:1'],
            'allocation_date' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:active,inactive,revoked'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allocation = $this->route('license_allocation');
            $allocationId = is_object($allocation) ? $allocation->id : $allocation;
            $allocationModel = is_object($allocation) ? $allocation : LicenseAllocation::find($allocationId);

            if (! $allocationModel) {
                return;
            }

            $inventoryId = $this->input('license_inventory_id', $allocationModel->license_inventory_id);
            $inventory = LicenseInventory::find($inventoryId);

            if ($inventory && $this->filled('allocated_quota')) {
                // Hitung alokasi aktif lain selain record saat ini
                $otherAllocated = (int) $inventory->activeAllocations()
                    ->where('id', '!=', $allocationModel->id)
                    ->sum('allocated_quota');

                $available = max(0, $inventory->quota_limit - $otherAllocated);
                $status = $this->input('status', $allocationModel->status);

                if ($status === 'active' && (int) $this->allocated_quota > $available) {
                    $validator->errors()->add(
                        'allocated_quota',
                        "Kuota alokasi ({$this->allocated_quota}) melebihi kuota tersedia ({$available} seat)."
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_inventory_id.required' => 'Lisensi software wajib dipilih.',
            'license_inventory_id.exists' => 'Lisensi software tidak valid.',
            'faculty_id.required' => 'Fakultas penerima wajib dipilih.',
            'faculty_id.exists' => 'Fakultas tidak valid.',
            'allocated_quota.required' => 'Kuota alokasi wajib diisi.',
            'allocated_quota.integer' => 'Kuota alokasi harus berupa bilangan bulat.',
            'allocated_quota.min' => 'Kuota alokasi minimal 1 kursi.',
            'allocation_date.required' => 'Tanggal penetapan alokasi wajib diisi.',
            'allocation_date.date' => 'Format tanggal penetapan alokasi tidak valid.',
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.date' => 'Format tanggal berakhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal berakhir harus sama dengan atau setelah tanggal mulai.',
            'status.required' => 'Status alokasi wajib dipilih.',
            'status.in' => 'Status alokasi harus berupa active, inactive, atau revoked.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
