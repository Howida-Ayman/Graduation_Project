<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model {
    use HasFactory;

    protected $fillable = [
        'code','is_active'
    ];



    public function teams() {
        return $this->hasMany(Team::class);
    }
    public function defenseCommittees()
{
    return $this->hasMany(DefenseCommittee::class, 'academic_year_id');
}
public function studentEnrollments()
{
    return $this->hasMany(StudentEnrollment::class);
}
}

