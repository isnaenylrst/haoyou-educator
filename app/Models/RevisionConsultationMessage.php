<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionConsultationMessage extends Model
{
    use HasFactory;

    protected $table = 'revision_consultation_messages';

    protected $fillable = [
        'revision_consultation_id',
        'sender_type',
        'user_id',
        'message',
        'attachment_path',
        'attachment_name',
        'decision',
    ];

    public function revisionConsultation()
    {
        return $this->belongsTo(RevisionConsultation::class, 'revision_consultation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
