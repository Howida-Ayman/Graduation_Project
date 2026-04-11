<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    protected $table = 'submission_files';

    protected $fillable = [
        'submission_id',
        'file_url',
        'original_name',
        'uploaded_at',
        'feedback',
        'graded_by_user_id',
        'graded_at',

    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * العلاقات
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}