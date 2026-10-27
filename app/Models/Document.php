<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    /** Jenis dokumen yang termasuk "Pemberitahuan Surat". */
    public const LETTER_TYPES = ['SURAT_LIBUR', 'SURAT_DINAS', 'LOA'];

    protected $table = 'documents';

    protected $fillable = [
        'user_id',
        'document_template_id',
        'program_level_id',
        'title',
        'document_type',
        'description',
        'file_path',
        'visibility',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($document) {
            if (empty($document->uploaded_at)) {
                $document->uploaded_at = now();
            }
        });
    }

    // ------------------------------------------------------------ RELASI

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /** User penerima (untuk surat ke satu guru, kompatibel dengan data lama). */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** User yang mengupload. */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Dipakai saat dokumen ini sertifikat kelulusan level tertentu
    public function programLevel()
    {
        return $this->belongsTo(ProgramLevel::class);
    }

    /** Penerima surat beserta waktu baca (pivot: read_at). */
    public function recipients()
    {
        return $this->belongsToMany(User::class, 'document_recipients', 'document_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    // ------------------------------------------------------------ SCOPE

    public function scopeSop($query)
    {
        return $query->where('document_type', 'SOP');
    }

    /**
     * SOP yang tampil untuk guru (dipakai notifikasi "SOP Baru" di
     * TeacherNotificationService). Dikembalikan setelah sempat terhapus.
     */
    public function scopeSopForTeachers($query)
    {
        return $query->where('document_type', 'SOP')
            ->where('visibility', 'Teacher');
    }

    public function scopeLetters($query)
    {
        return $query->whereIn('document_type', self::LETTER_TYPES);
    }

    // ------------------------------------------------------------ INFO FILE & LABEL

    public function getFileExistsAttribute(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));
    }

    public function getFileSizeLabelAttribute(): string
    {
        if (!$this->file_exists) {
            return '-';
        }

        $bytes = Storage::disk('public')->size($this->file_path);

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'SURAT_LIBUR' => 'Surat Libur',
            'SURAT_DINAS' => 'Surat Dinas',
            'LOA'         => 'LoA',
            default       => (string) $this->document_type,
        };
    }

    /** Ringkasan penerima (relasi `recipients.teacher` harus sudah dimuat). */
    public function getRecipientLabelAttribute(): string
    {
        if ($this->visibility === 'Teacher') {
            return 'Semua Guru (' . ($this->recipients_total ?? $this->recipients->count()) . ')';
        }

        $names = $this->recipients
            ->map(fn ($u) => $u->teacher->name ?? $u->username)
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return 'Guru tertentu';
        }

        return $names->take(3)->implode(', ')
            . ($names->count() > 3 ? ' dan ' . ($names->count() - 3) . ' lainnya' : '');
    }

    /** Jumlah surat yang belum dibuka oleh user (guru) ini. */
    public static function unreadLettersFor(int $userId): int
    {
        return DB::table('document_recipients as r')
            ->join('documents as d', 'd.id', '=', 'r.document_id')
            ->whereIn('d.document_type', Document::LETTER_TYPES)
            ->where('r.user_id', $userId)
            ->whereNull('r.read_at')
            ->count();
    }
}