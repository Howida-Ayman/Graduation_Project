<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestedProject extends Model
{
    //

    protected $fillable = [
        'title',
        'description',
        'student_id',
        'department_id',
        'is_active',
        'recommended_tools',  
        'created_by_admin_id',
        'department_id',
        
        ];

    public function department()
{
    return $this->belongsTo(Department::class);
}
}
