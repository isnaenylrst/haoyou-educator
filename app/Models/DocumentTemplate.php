<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $table = 'document_templates';

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
        return $this->hasMany(Document::class, 'document_template_id');
    }

    public function scopeProgressReport($query)
    {
        return $query->where('document_type', 'Progress Report');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    // ------------------------------------------------------------ INFO FILE

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
}