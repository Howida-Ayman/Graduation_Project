<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    //
    protected $fillable = [
        'id',
        'academic_year_id',
        'department_id',
        'leader_user_id'
        

    ];



    public function academicYear()
{
    return $this->belongsTo(AcademicYear::class);
}
}
