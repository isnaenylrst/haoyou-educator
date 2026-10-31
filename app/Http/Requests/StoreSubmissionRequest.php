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
            'type'            => ['required', Rule::in(Submission::TYPES)],
            'item_name'       => ['required', 'string', 'max:255'],
            'amount'          => ['nullable', 'numeric', 'min:0'],
            'date'            => ['required', 'date'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'receipt_file'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'photo_file'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'items'           => ['nullable', 'array', 'min:1'],
            'items.*.name'    => ['required_with:items', 'string', 'max:255'],
            'items.*.amount'  => ['required_with:items', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'         => 'Pilih jenis pengajuan.',
            'item_name.required'    => 'Nama barang / keperluan utama wajib diisi.',
            'date.required'         => 'Tanggal pengajuan / nota wajib diisi.',
            'receipt_file.mimes'    => 'Format nota/bukti harus PDF, JPG, PNG, atau WEBP.',
            'receipt_file.max'      => 'Ukuran nota/bukti maksimal 10 MB.',
            'photo_file.mimes'      => 'Format foto bahan ajar harus JPG, PNG, atau WEBP.',
            'photo_file.max'        => 'Ukuran foto bahan ajar maksimal 10 MB.',
            'items.*.name.required' => 'Nama item pada rincian multi-item wajib diisi.',
            'items.*.amount.min'    => 'Nominal per item minimal Rp 1.',
        ];
    }
}

