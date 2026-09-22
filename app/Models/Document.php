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

    // Baru: dipakai saat dokumen ini sertifikat kelulusan level tertentu
    public function programLevel()
    {
        return $this->belongsTo(ProgramLevel::class);
    }
}
