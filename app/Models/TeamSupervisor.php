<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamSupervisor extends Model
{
    protected $table = 'team_supervisors';

    protected $fillable = [
        'team_id',
        'supervisor_user_id',
        'supervisor_role',
        'assigned_at',
        'ended_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * العلاقات
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}