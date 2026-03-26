<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'team_id',
        'request_type',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'type' => 'string',
    ];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}