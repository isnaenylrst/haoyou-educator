<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

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


    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($document) {

            if (empty($document->uploaded_at)) {

                $document->uploaded_at = now();

            }

        });
    }


    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function template()
    {
        return $this->belongsTo(
            DocumentTemplate::class,
            'document_template_id'
        );
    }


    /*
    | User penerima
    */

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }


    /*
    | User yang mengupload
    */

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

        public function scopeSop($query)
    {
        return $query->where('document_type', 'SOP');
    }

    public function getFileExistsAttribute(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
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

    // Baru: dipakai saat dokumen ini sertifikat kelulusan level tertentu
    public function programLevel()
    {
        return $this->belongsTo(ProgramLevel::class);
    }
}
