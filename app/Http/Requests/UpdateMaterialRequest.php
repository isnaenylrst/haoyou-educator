<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Material|null $material */
        $material = $this->route('material');
        $audience = $material?->audience ?? $this->input('audience');

        $levelRule = ['nullable', 'integer'];
        if ($this->filled('program_id')) {
            $levelRule[] = Rule::exists('program_levels', 'id')->where(fn ($q) => $q->where('category_id', $this->program_id));
        } else {
            $levelRule[] = 'exists:program_levels,id';
        }

        return [
            'audience'       => ['nullable'],
            'program_id'     => ['nullable', 'integer', 'exists:program_categories,id'],
            'level_id'       => $levelRule,
            'meeting_number' => [
                'required', 'integer', 'min:1', 'max:255',
                Rule::unique('materials', 'meeting_number')
                    ->where(fn ($q) => $q->where('level_id', $this->level_id)
                        ->where('program_id', $this->program_id)
                        ->where('audience', $audience))
                    ->ignore($material?->id),
            ],
            'title'          => ['required', 'string', 'max:255'],
            'syllabus'       => ['nullable', 'string'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'cco'            => ['nullable', 'string'],
            'lesson_plan'    => ['nullable', 'string'],
            'activity'       => ['nullable', 'string'],
            'box_code'       => ['nullable', 'string', 'max:50'],
            'equipment_list' => ['nullable', 'string'],
            'material_file'  => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:10240'],
            'vocabularies'   => ['nullable', 'string', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'level_id.exists'        => 'Level yang dipilih tidak sesuai dengan program yang dipilih.',
            'meeting_number.unique'  => 'Pertemuan ini sudah ada pada program, level, dan tujuan yang sama.',
            'meeting_number.max'     => 'Nomor pertemuan maksimal 255.',
            'material_file.mimes'    => 'File harus berformat PDF, DOC, DOCX, PPT, atau PPTX.',
            'material_file.max'      => 'Ukuran file maksimal 10 MB.',
            'material_file.uploaded' => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'audience'       => 'tujuan materi',
            'program_id'     => 'program',
            'level_id'       => 'level',
            'meeting_number' => 'pertemuan',
            'title'          => 'judul materi',
            'syllabus'       => 'silabus',
            'description'    => 'deskripsi',
            'cco'            => 'CCO',
            'lesson_plan'    => 'lesson plan',
            'activity'       => 'aktivitas',
            'box_code'       => 'kode box',
            'equipment_list' => 'perlengkapan',
            'material_file'  => 'file materi',
            'vocabularies'   => 'kosakata',
        ];
    }
}

