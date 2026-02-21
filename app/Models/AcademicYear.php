<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model {
    use HasFactory;

    protected $fillable = [
        'code',
    ];

    public function students() {
        return $this->hasMany(StudentProfile::class);
    }

    public function teams() {
        return $this->hasMany(Team::class);
    }

    public function projectRules() {
        return $this->hasMany(ProjectRule::class);
    }

    public function milestones() {
        return $this->hasMany(Milestone::class);
    }
}

