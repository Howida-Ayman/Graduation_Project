<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneCommittee extends Model
{
    protected $fillable = [
        'team_id',
        'created_by_admin_id',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function members()
    {
        return $this->hasMany(MilestoneCommitteeMember::class, 'committee_id');
    }

    public function grades()
    {
        return $this->hasMany(MilestoneCommitteeGrade::class, 'committee_id');
    }
}