<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{

    protected $primaryKey = 'user_id'; 
    public $incrementing = false; // لأنه مش auto-increment
    protected $keyType = 'int';



    protected $fillable = [
        'user_id',
        'department_id',
        'gpa',
        'has_graduated'
    ];

    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}


    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function enrollments()
{
    return $this->hasMany(StudentEnrollment::class, 'student_user_id', 'user_id');
}

}
