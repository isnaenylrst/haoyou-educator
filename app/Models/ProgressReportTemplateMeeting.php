<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressReportTemplateMeeting extends Model
{
    protected $fillable = ['template_id', 'meeting_number', 'materials'];
}