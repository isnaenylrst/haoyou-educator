<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnerApproveSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->level?->nama_level === 'Owner';
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:Approved,Rejected'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan approval wajib dipilih.',
            'decision.in'       => 'Keputusan harus berupa Approved atau Rejected.',
            'note.max'          => 'Catatan maksimal 1000 karakter.',
        ];
    }
}

