<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTask extends Model
{
    protected $table = 'class_tasks';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'task_type',
        'material_id',
        'title',
        'description',
        'due_date',
        'status',
        'priority',
        'done_at',
        'teacher_note',
        'attachment_path',
        'attachment_name',
        'created_by',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    protected $casts = [
        'due_date' => 'date',
        'done_at'  => 'datetime',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teacherMaterials()
    {
        return $this->hasMany(TeacherMaterial::class, 'task_id');
    }

    public function latestSubmission()
    {
        return $this->hasOne(TeacherMaterial::class, 'task_id')->latestOfMany();
    }

    /**
     * Virtual display status untuk tugas perbaikan yang terintegrasi dengan pengajuan LP/PPT:
     * - Selesai: jika task->status == 'Done'
     * - Perlu Revisi: jika submission terbaru berstatus 'Perlu Revisi' atau 'Rejected'
     * - Dikerjakan / Diajukan: jika submission terbaru berstatus 'Pending' atau 'Diajukan'
     * - Ditugaskan: jika belum ada submission
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'Done') {
            return 'Selesai';
        }

        $latest = $this->relationLoaded('latestSubmission')
            ? $this->latestSubmission
            : ($this->relationLoaded('teacherMaterials') ? $this->teacherMaterials->sortByDesc('id')->first() : $this->latestSubmission()->first());

        if ($latest) {
            if (in_array($latest->status, ['Perlu Revisi', 'Rejected'], true)) {
                return 'Perlu Revisi';
            }
            if (in_array($latest->status, ['Approved', 'Disetujui'], true)) {
                return 'Selesai';
            }
            return 'Diajukan';
        }

        return 'Ditugaskan';
    }

    /** Tugas yang belum diselesaikan guru. */
    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }

    /** Tugas terbuka yang batas waktunya sudah lewat (sebelum hari ini). */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Open')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'Open'
            && $this->due_date
            && $this->due_date->copy()->endOfDay()->isPast();
    }
}