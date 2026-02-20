<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'id',
        'name',
        'is_active'
    ];
    public function staffProfiles()
{
    return $this->hasMany(StaffProfile::class);
}
public function studentProfiles()
{
    return $this->hasMany(StudentProfile::class);
}


}
