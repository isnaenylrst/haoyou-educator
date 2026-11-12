<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->level?->nama_level === 'Owner';
    }

    public function prepareForValidation(): void
    {
        // Backward compatibility: jika input mengirim single target_role
        if (!$this->has('target_roles') && $this->has('target_role')) {
            $role = $this->input('target_role');
            $roles = match ($role) {
                'Admin'                   => ['Admin'],
                'Curriculum', 'Kurikulum' => ['Curriculum'],
                'Teacher', 'Guru'         => ['Teacher'],
                'All'                     => ['Admin', 'Curriculum', 'Teacher'],
                default                   => is_array($role) ? $role : [$role],
            };
            $this->merge(['target_roles' => $roles]);
        }
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'category'       => ['required', 'string', 'max:100'],
            'target_roles'   => ['required', 'array', 'min:1'],
            'target_roles.*' => ['required', 'string', 'in:Admin,Curriculum,Teacher'],
            'level'          => ['required', 'in:Umum,Dasar,Menengah,Lanjutan'],
            'type'           => ['required', 'in:document,video'],
            'version'        => ['nullable', 'string', 'max:20'],
            'description'    => ['nullable', 'string'],
            'thumbnail'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            // Dokumen: jika tipe document, wajib file
            'file'           => [
                'nullable',
                'required_if:type,document',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar',
                'max:51200'
            ],

            // Video: jika tipe video, wajib salah satu antara video_file atau video_url
            'video_file'     => [
                'nullable',
                'required_without_all:video_url,file',
                'file',
                'mimes:mp4,mov,avi,webm',
                'max:102400'
            ],
            'video_url'      => [
                'nullable',
                'url',
                'max:255'
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'video') {
                $hasFile = $this->hasFile('video_file');
                $hasUrl = filled($this->input('video_url'));

                if (!$hasFile && !$hasUrl) {
                    $validator->errors()->add('video_file', 'Untuk materi tipe Video, unggah file video atau masukkan link video.');
                    $validator->errors()->add('video_url', 'Untuk materi tipe Video, unggah file video atau masukkan link video.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required'          => 'Judul materi training wajib diisi.',
            'category.required'       => 'Kategori materi wajib dipilih atau diisi.',
            'target_roles.required'   => 'Pilih minimal satu peran yang dituju untuk materi ini.',
            'target_roles.min'        => 'Pilih minimal satu peran yang dituju untuk materi ini.',
            'target_roles.*.in'       => 'Pilihan peran yang dituju tidak valid.',
            'level.required'          => 'Pilih level materi (Umum, Dasar, Menengah, atau Lanjutan).',
            'type.required'           => 'Pilih tipe materi (Dokumen/SOP atau Video).',
            'thumbnail.max'           => 'Ukuran thumbnail maksimal 5 MB.',
            'file.required_if'        => 'File dokumen wajib diunggah untuk materi tipe Dokumen/SOP.',
            'file.max'                => 'Ukuran file dokumen maksimal 50 MB.',
            'file.mimes'              => 'File dokumen harus berformat pdf, doc, docx, ppt, pptx, xls, xlsx, zip, atau rar.',
            'video_file.max'          => 'Ukuran file video maksimal 100 MB.',
            'video_file.mimes'        => 'Format video harus berformat mp4, mov, avi, atau webm.',
            'video_url.url'           => 'Format link video harus berupa URL valid (contoh: https://www.youtube.com/watch?v=...).',
        ];
    }
}
