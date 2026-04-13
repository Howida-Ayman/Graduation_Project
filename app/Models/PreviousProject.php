<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreviousProject extends Model
{
   protected $fillable = [
    'academic_year_id',
    'team_id',
    'proposal_id',
    'final_score',
    'feedback',
    'graded_by',
    'graded_at',
    'archived_at',
];

    

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function academicYear()
{
    return $this->belongsTo(AcademicYear::class);
}

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function favorites()
{
    return $this->belongsToMany(
        User::class,
        'previous_project_favorites',
        'previous_project_id',
        'student_user_id'
    );
}




}