<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseCommitteeMember extends Model
{
    protected $table='defense_committee_members';
     protected $fillable = [
        'committee_id',
        'member_user_id',
        'member_role',
        'seat_order',
    ];

    protected $casts = [
        'member_role' => 'string',
        'seat_order' => 'integer',
    ];

    public function committee()
    {
        return $this->belongsTo(DefenseCommittee::class, 'committee_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }
}
