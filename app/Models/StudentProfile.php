<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'gpa',
        'level'
    ];

    protected $casts = [
    'level' => \App\StudentLevel::class,
];

}
