<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $table = 'document_templates';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'document_type',
        'file_path',
        'description',
        'uploaded_by',
        'status',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}