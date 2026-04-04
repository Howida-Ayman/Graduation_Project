<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseGrade extends Model
{
    protected $table='defense_grades';
     protected $fillable = [
        'committee_id',
        'entered_by_user_id',
        'grade',
        'notes',
        'entered_at',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'entered_at' => 'datetime',
    ];

    public function committee()
    {
        return $this->belongsTo(DefenseCommittee::class, 'committee_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by_user_id');
    }
}
