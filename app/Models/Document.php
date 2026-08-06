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


    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeSop($query)
    {
        return $query->where(
            'document_type',
            'SOP'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSOR FILE URL
    |--------------------------------------------------------------------------
    */

    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url(
            $this->file_path
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSOR EXTENSION
    |--------------------------------------------------------------------------
    */

    public function getExtensionAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        return strtoupper(
            pathinfo(
                $this->file_path,
                PATHINFO_EXTENSION
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSOR NAMA JENIS SURAT
    |--------------------------------------------------------------------------
    */

    public function getLetterTypeNameAttribute()
    {
        return match ($this->document_type) {

            'SURAT_LIBUR' =>
                'Surat Libur',

            'SURAT_DINAS' =>
                'Surat Dinas',

            'LOA' =>
                'LoA',

            'SURAT' =>
                'Surat',

            default =>
                $this->document_type,

        };
    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSOR NAMA PENERIMA
    |--------------------------------------------------------------------------
    */

    public function getRecipientNameAttribute()
    {
        if ($this->visibility === 'Teacher') {

            return 'Semua Guru';

        }

        if (
            $this->visibility === 'Private'&& $this->user) {

            return $this->user->name;

        }

        return '-';
    }
}  