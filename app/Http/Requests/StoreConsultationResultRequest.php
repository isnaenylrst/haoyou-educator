<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum']);
    }

    public function rules(): array
    {
        return [
            'teacher_id'           => ['required', 'exists:teachers,id'],
            'participant_names'    => ['nullable', 'string', 'max:255'],
            'consultation_date'    => ['required', 'date'],
            'topic'                => ['required', 'string', 'max:255'],
            'improvement_points'   => ['required', 'array', 'min:1'],
            'improvement_points.*' => ['required', 'string', 'max:500'],
            'follow_up'            => ['nullable', 'string'],
            'observation_id'       => ['nullable', 'exists:teacher_observations,id'],
            'file'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_id.required'         => 'Pilih guru yang berkonsultasi.',
            'consultation_date.required'  => 'Tanggal konsultasi wajib diisi.',
            'topic.required'              => 'Topik konsultasi wajib diisi.',
            'improvement_points.required' => 'Minimal masukkan satu poin yang perlu diperbaiki.',
            'improvement_points.min'      => 'Minimal masukkan satu poin yang perlu diperbaiki.',
            'file.max'                    => 'Ukuran file lampiran maksimal 20 MB.',
            'file.mimes'                  => 'Format file harus berupa PDF, JPG, PNG, DOC, DOCX, XLS, atau XLSX.',
        ];
    }
}

