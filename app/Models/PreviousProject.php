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
public function scopeFilter($query, $filters)
{
    return $query
        ->when($filters['search'] ?? null, function ($q, $search) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('title', 'like', "%{$search}%")
            );
        })
        ->when($filters['department'] ?? null, function ($q, $department) {
            $q->whereHas('proposal.department', fn($q2) =>
                $q2->where('name', $department)
            );
        })
        ->when($filters['technology'] ?? null, function ($q, $technology) {
            $q->whereHas('proposal', fn($q2) =>
                $q2->where('technologies', 'like', "%{$technology}%")
            );
        })
        ->when($filters['year'] ?? null, function ($q, $year) {
            $q->whereHas('proposal.team', fn($q2) =>
                $q2->where('academic_year_id', $year)
            );
        });
}




}