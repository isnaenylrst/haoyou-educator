<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->level?->nama_level === 'Owner';
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'file'        => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar', 'max:51200'],
            'video_file'  => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'video_url'   => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'    => 'Judul materi training wajib diisi.',
            'category.required' => 'Kategori materi wajib dipilih atau diisi.',
            'file.max'          => 'Ukuran file dokumen maksimal 50 MB.',
            'file.mimes'        => 'File dokumen harus berformat pdf, doc, docx, ppt, pptx, xls, xlsx, zip, atau rar.',
            'video_file.max'    => 'Ukuran file video maksimal 100 MB.',
            'video_file.mimes'  => 'Format video harus berformat mp4, mov, avi, atau webm.',
            'video_url.url'     => 'Format link video harus berupa URL valid.',
        ];
    }
}

