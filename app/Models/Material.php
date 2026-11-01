<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $level_id
 * @property string $audience
 * @property int $meeting_number
 * @property string $title
 * @property string|null $description
 * @property string|null $material_file_path
 * @property-read bool $file_exists
 * @property-read bool $is_pdf
 * @property-read string $file_extension
 * @property-read string $file_size_label
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Material forGuru()
 * @method static \Illuminate\Database\Eloquent\Builder|Material forSiswa()
 * @method static \Illuminate\Database\Eloquent\Builder|Material forHuahua()
 */

class Material extends Model
{
    use HasFactory;

        public const AUDIENCES = ['Guru', 'Siswa', 'Huahua'];

    protected $fillable = [
        'program_id',
        'level_id',
        'uploaded_by',
        'meeting_number',
        'title',
        'syllabus',
        'cco',
        'lesson_plan',
        'activity',
        'box_code',
        'equipment_list',
        'material_file_path',
        'audience',
        'description',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramCategory::class, 'program_id');
    }

    public function getProgramNameAttribute(): string
    {
        return $this->program?->category_name ?? 'Umum';
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Kosakata materi, diurutkan berdasarkan order_number.
    public function vocabs(): HasMany
    {
        return $this->hasMany(MaterialVocab::class, 'material_id')
            ->orderBy('order_number');
    }

    // Alias relasi untuk kode yang menggunakan vocabularies().
    public function vocabularies(): HasMany
    {
        return $this->hasMany(MaterialVocab::class, 'material_id');
    }

    public function teacherMaterials(): HasMany
    {
        return $this->hasMany(TeacherMaterial::class, 'material_id');
    }

    public function teachingJournals(): HasMany
    {
        return $this->hasMany(TeachingJournal::class, 'material_id');
    }

        public function scopeForGuru($query)
    {
        return $query->where('audience', 'Guru');
    }

    public function scopeForSiswa($query)
    {
        return $query->where('audience', 'Siswa');
    }

    public function scopeForHuahua($query)
    {
        return $query->where('audience', 'Huahua');
    }

    /** Kata di bank Huahua yang berasal dari materi ini. */
    public function huahuaWords(): HasMany
    {
        return $this->hasMany(VocabWord::class, 'material_id')->orderBy('order_number');
    }

    // Informasi file materi.
    public function getFileExtensionAttribute(): string
    {
        return strtolower(
            pathinfo((string) $this->material_file_path, PATHINFO_EXTENSION)
        );
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_extension === 'pdf';
    }

    public function getFileExistsAttribute(): bool
    {
        return $this->material_file_path !== null
            && $this->material_file_path !== ''
            && Storage::disk('public')->exists($this->material_file_path);
    }

    public function getFileSizeLabelAttribute(): string
    {
        if (!$this->file_exists) {
            return '-';
        }

        $bytes = Storage::disk('public')->size($this->material_file_path);

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
