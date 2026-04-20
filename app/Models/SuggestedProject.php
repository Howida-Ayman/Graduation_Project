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
        
        ];

    public function department()
{
    return $this->belongsTo(Department::class);
}

// أضيفي العلاقة دي
public function favorites()
{
    return $this->belongsToMany(
        User::class,
        'suggested_project_favorites',
        'suggested_project_id',
        'student_user_id'
    )->withTimestamps();
}

// Helper method to check if user favorited
public function isFavoritedBy($userId)
{
    return $this->favorites()->where('student_user_id', $userId)->exists();
}
public function scopeFilter($query, $filters)
{
    return $query
        ->when($filters['search'] ?? null, fn($q, $search) =>
            $q->where('title', 'like', "%{$search}%")
        )
        ->when($filters['department'] ?? null, fn($q, $department) =>
            $q->where('department_id', $department)
        )
        ->when($filters['technology'] ?? null, fn($q, $technology) =>
            $q->where('recommended_tools', 'like', "%{$technology}%")
        );
}





}
