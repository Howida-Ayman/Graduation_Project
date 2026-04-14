<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Milestone extends Model
{
    protected $table = 'milestones';
    protected $fillable = [
        'title',
        'description',
        'phase_number',
        'start_date',
        'deadline',
        'status',
        'is_open',
        'is_forced_open',
        'is_forced_closed',
        'is_active',
        'notes',
    ];
    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'deadline' => 'date:Y-m-d',
        'is_open' => 'boolean',
        'status' => 'string',
        'is_forced_open' => 'boolean',
        'is_forced_closed' => 'boolean',
    ];



    /**
     * العلاقات
     */
    

    public function requirements(): HasMany
    {
        return $this->hasMany(MilestoneRequirement::class);
    }

    /**
     * النطاقات (Scopes)
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }


    public function scopeOrdered($query)
    {
        return $query->orderBy('phase_number');
    }

    /**
     * الوظائف المساعدة
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'completed' => 'bg-green-100 text-green-800',
            'on_progress' => 'bg-blue-100 text-blue-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'completed';
    }

    public function getProgressAttribute(): float
    {
        if ($this->status === 'completed') {
            return 100;
        }

        $totalRequirements = $this->requirements()->count();
        if ($totalRequirements === 0) {
            return 0;
        }

        $completedRequirements = $this->requirements()
            ->whereHas('submissions', function($q) {
                $q->whereNotNull('graded_at');
            })->count();

        return round(($completedRequirements / $totalRequirements) * 100, 2);
    }
    // العلاقة مع submissions
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
     // العلاقة مع team_milestones
    public function teamMilestonestatus()
    {
        return $this->hasMany(TeamMilestonStatus::class);
    }
     // علاقة مباشرة مع teams
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_milestone_status')
            ->withPivot('status')
            ->withTimestamps();
    }
}