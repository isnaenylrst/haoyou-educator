<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\ClassScheduleService;

class ClassSession extends Model
{
    protected $table = 'class_sessions';

    protected $fillable = [
        'class_id',
        'class_enrollment_id',
        'class_schedule_id',
        'kind',
        'session_number',
        'session_date',
        'start_time',
        'end_time',
        'room',
        'teacher_id',
        'location_type',
        'status',
        'original_date',
        'change_reason',
    ];

    protected $casts = [
        'session_date'  => 'date',
        'original_date' => 'date',
    ];

    /* ---------------------------------------------------------- */
    /*  Relasi                                                     */
    /* ---------------------------------------------------------- */

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /** Pendaftaran siswa yang diberi kelas pengganti (null untuk pertemuan reguler). */
    public function enrollment(): BelongsTo
    {
        // withTrashed: nama siswa tetap tampil di riwayat walau pendaftarannya sudah di-soft-delete
        return $this->belongsTo(ClassEnrollment::class, 'class_enrollment_id')->withTrashed();
    }

    /** Biaya tambahan (kelas pengganti tambahan / ke rumah murid). */
    public function fees(): HasMany
    {
        return $this->hasMany(SessionFee::class, 'class_session_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /** Jurnal mengajar untuk pertemuan ini (ada setelah guru mengisi jurnal). */
    public function journal(): HasOne
    {
        return $this->hasOne(TeachingJournal::class, 'class_session_id');
    }

    /* ---------------------------------------------------------- */
    /*  Scope                                                      */
    /* ---------------------------------------------------------- */

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'Scheduled');
    }

    /** Pertemuan yang belum terjadi (hari ini ke depan). */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today());
    }

    /** Pertemuan yang ikut hitungan paket (lihat countsTowardPackage). */
    public function scopeCountsTowardPackage(Builder $query): Builder
    {
        return $query->where('status', '!=', 'Cancelled')->where('kind', 'Regular');
    }

    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeOnDate(Builder $query, $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    /**
     * Pertemuan yang jamnya beririsan dengan rentang $start-$end.
     * Dipakai untuk cek bentrok guru. Pertemuan batal tidak dihitung.
     */
    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->where('status', '!=', 'Cancelled')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    /* ---------------------------------------------------------- */
    /*  Helper                                                     */
    /* ---------------------------------------------------------- */

    /** Pertemuan yang sudah selesai atau sudah punya jurnal tidak boleh diubah/dihapus. */
    public function isLocked(): bool
    {
        if ($this->status === 'Completed') {
            return true;
        }

        // Pakai relasi yang sudah di-eager-load kalau ada (hindari N+1 di halaman jadwal)
        return $this->relationLoaded('journal')
            ? $this->journal !== null
            : $this->journal()->exists();
    }

    /** Boleh ditandai selesai? (Scheduled, tanggalnya sudah tiba, belum ada jurnal) */
    public function canBeCompleted(): bool
    {
        return $this->status === 'Scheduled'
            && ! $this->isLocked()
            && $this->session_date->lte(today());
    }

    /** Masih boleh dipindah jadwalnya? (Scheduled, belum ada jurnal, belum lewat) */
    public function canBeRescheduled(): bool
    {
        return $this->status === 'Scheduled'
            && ! $this->isLocked()
            && $this->session_date->gte(today());
    }

    /**
     * Pertemuan ini menyimpang dari pola mingguannya (dipindah hari/jam/ruang satu kali)?
     * Dihitung dari datanya sendiri, jadi tidak perlu kolom penanda.
     * Kelas pengganti / pertemuan manual (tanpa pola) selalu dianggap tidak menyimpang.
     */
    public function deviatesFromPattern(): bool
    {
        $p = $this->schedule;

        if (! $p) {
            return false;
        }

        return ClassScheduleService::DAYS[$this->session_date->dayOfWeekIso - 1] !== $p->day
            || substr($this->start_time, 0, 5) !== substr($p->start_time, 0, 5)
            || substr($this->end_time, 0, 5) !== substr($p->end_time, 0, 5)
            // Ruang hanya dibandingkan untuk pertemuan di studio (Home Visit tidak punya ruang)
            || ($this->location_type === 'Studio' && (string) $this->room !== (string) $p->room);
    }

    public function isHomeVisit(): bool
    {
        return $this->location_type === 'Home Visit';
    }

    public function isReplacement(): bool
    {
        return $this->kind === 'Replacement';
    }

    /** Ikut hitungan pertemuan paket? Hanya pertemuan reguler. Pertemuan tambahan (Replacement) di luar paket. */
    public function countsTowardPackage(): bool
    {
        return $this->status !== 'Cancelled' && $this->kind === 'Regular';
    }
}