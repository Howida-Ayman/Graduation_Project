<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneCommitteeMember extends Model
{
    protected $fillable = [
        'committee_id',
        'member_user_id',
        'member_role',
    ];

    public function committee()
    {
        return $this->belongsTo(MilestoneCommittee::class, 'committee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }
}