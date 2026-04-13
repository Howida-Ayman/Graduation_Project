<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'academic_year_id',
        'team_id',
        'submitted_by_user_id',
        'department_id',
        'project_type_id',
        'title',
        'description',
        'problem_statement',
        'solution',
        'technologies',
        'category',
        'attachment_file',
        'image_url',
        'status',
        'decided_by_admin_id',
        'decided_at',
        'admin_notes',
    ];

    /*
    |-----------------------------------
    | Relationships
    |-----------------------------------
    */

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function academicYear()
    {
    return $this->belongsTo(AcademicYear::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'decided_by_admin_id');
    }

    public function previousProject()
    {
        return $this->hasOne(PreviousProject::class);
    }
}