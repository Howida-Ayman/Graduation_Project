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
    ];

    public Function user()
    {
        return $this->hasMany(User::class);
    }


    public function department()
    {
        return $this->belongsTo(Department::class);
    }

}
