<?php

namespace App\Http\Requests;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type'         => ['required', Rule::in(Submission::TYPES)],
            'item_name'    => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:1'],
            'date'         => ['required', 'date'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'      => 'Pilih jenis pengajuan.',
            'item_name.required' => 'Nama barang / sertifikat wajib diisi.',
            'amount.required'    => 'Nominal pengajuan wajib diisi.',
            'amount.min'         => 'Nominal minimal Rp 1.',
            'date.required'      => 'Tanggal pengajuan / nota wajib diisi.',
            'receipt_file.mimes' => 'Format nota/bukti harus PDF, JPG, PNG, atau WEBP.',
            'receipt_file.max'   => 'Ukuran nota/bukti maksimal 5 MB.',
        ];
    }
}

