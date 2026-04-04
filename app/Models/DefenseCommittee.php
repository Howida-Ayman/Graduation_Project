<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseCommittee extends Model
{
    protected $table='defense_committees';
    protected $fillable = [
        'academic_year_id',
        'team_id',
        'scheduled_at',
        'location',
        'created_by_admin_id',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'status' => 'string',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

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
        return $this->hasMany(DefenseCommitteeMember::class, 'committee_id');
    }

    public function doctors()
    {
        return $this->hasMany(DefenseCommitteeMember::class, 'committee_id')
            ->where('member_role', 'doctor')
            ->orderBy('seat_order');
    }

    public function teachingAssistant()
    {
        return $this->hasOne(DefenseCommitteeMember::class, 'committee_id')
            ->where('member_role', 'ta');
    }

    public function grade()
    {
        return $this->hasOne(DefenseGrade::class, 'committee_id');
    }
}
