<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
     protected $fillable = [
        'team_id',
        'scheduled_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
