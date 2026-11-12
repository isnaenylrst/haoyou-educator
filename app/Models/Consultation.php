<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    public const TYPES = ['Materi', 'Materi_Baru', 'LP_PPT', 'Direktur', 'Trial_Teaching'];

    protected $table = 'consultations';

    protected $fillable = [
        'teacher_id',
        'teacher_name',
        'teacher_phone',
        'class_id',
        'type',
        'scheduled_at',
        'deadline_at',
        'status',
        'notify_whatsapp',
        'notes',
        'created_by',
        'teacher_material_id',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'deadline_at'     => 'date',
        'notify_whatsapp' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teacherMaterial()
    {
        return $this->belongsTo(TeacherMaterial::class, 'teacher_material_id');
    }

    /** Kelas terkait (wajib untuk Konsultasi Materi Baru). */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /** Konsultasi yang masih berjalan (belum selesai / dibatalkan). */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Belum Terjadwal', 'Terjadwal']);
    }

    /** Masih berjalan tetapi deadline-nya sudah lewat. */
    public function scopeOverdue($query)
    {
        return $query->active()
            ->whereNotNull('deadline_at')
            ->whereDate('deadline_at', '<', today());
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'Materi'         => 'Konsultasi Materi',
            'Materi_Baru'    => 'Konsultasi Materi Baru',
            'LP_PPT'         => 'Konsultasi LP & PPT (Kurikulum)',
            'Direktur'       => 'Konsultasi Direktur',
            'Trial_Teaching' => 'Trial Teaching',
            default          => (string) $this->type,
        };
    }

    /** Nama guru yang ditampilkan: dari akun guru terdaftar, atau yang diketik manual. */
    public function getTeacherDisplayNameAttribute(): string
    {
        return $this->teacher?->name ?? ($this->teacher_name ?: '-');
    }

    /** true jika gurunya belum terdaftar di website (mis. trial teaching). */
    public function getIsUnregisteredTeacherAttribute(): bool
    {
        return $this->teacher_id === null;
    }

    /** Sisa hari ke deadline (negatif = sudah lewat). Null jika tidak relevan. */
    public function getDeadlineDaysAttribute(): ?int
    {
        if (!$this->deadline_at || !in_array($this->status, ['Belum Terjadwal', 'Terjadwal'], true)) {
            return null;
        }

        return (int) today()->diffInDays($this->deadline_at, false);
    }

    /** late | soon (<= 3 hari) | ok | null */
    public function getDeadlineStateAttribute(): ?string
    {
        $days = $this->deadline_days;

        if ($days === null) {
            return null;
        }

        return $days < 0 ? 'late' : ($days <= 3 ? 'soon' : 'ok');
    }

    /** Keterangan untuk guru: kelas, deadline, pengajuan terkait, dan catatan Kurikulum. */
    public function getContextNoteAttribute(): ?string
    {
        $lines = [];

        if ($this->class) {
            $lines[] = 'Kelas: ' . $this->class->class_name;
        }
        if ($this->deadline_at) {
            $lines[] = 'Deadline konsultasi: ' . $this->deadline_at->translatedFormat('d F Y');
        }
        if ($this->teacherMaterial?->ppt_title) {
            $lines[] = 'Terkait pengajuan: ' . $this->teacherMaterial->ppt_title;
        }
        if ($this->notes) {
            $lines[] = $this->notes;
        }

        return $lines ? implode("\n", $lines) : null;
    }
}