<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'category' => 'required|string|in:Freeware,Commercial,OpenSource,Shareware,Other',
            'status' => 'required|string|in:Whitelist,Blacklist,Unreviewed',
            'description' => 'nullable|string',
        ];
    }
}
