<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->level?->nama_level === 'Owner';
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'category'       => ['required', 'string', 'max:100'],
            'target_role'    => ['required', 'in:All,Admin,Curriculum,Teacher'],
            'level'          => ['required', 'in:Umum,Dasar,Menengah,Lanjutan'],
            'type'           => ['required', 'in:document,video'],
            'version'        => ['nullable', 'string', 'max:20'],
            'description'    => ['nullable', 'string'],
            'thumbnail'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'file'           => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar', 'max:51200'],
            'video_file'     => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'video_url'      => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Judul materi training wajib diisi.',
            'category.required'    => 'Kategori materi wajib dipilih atau diisi.',
            'target_role.required' => 'Pilih peran yang dituju untuk materi ini.',
            'level.required'       => 'Pilih level materi.',
            'type.required'        => 'Pilih tipe materi.',
            'file.max'             => 'Ukuran file dokumen maksimal 50 MB.',
            'video_file.max'       => 'Ukuran file video maksimal 100 MB.',
            'video_url.url'        => 'Format link video harus berupa URL valid.',
        ];
    }
}
