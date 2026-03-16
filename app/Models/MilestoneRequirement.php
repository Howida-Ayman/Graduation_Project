<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilestoneRequirement extends Model
{
    protected $table = 'milestone_requirements';

    protected $fillable = [
        'milestone_id',
        'requirement',
    ];

    /**
     * العلاقات
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'milestone_requirement_id');
    }

    /**
     * التحقق من وجود تسليم لفريق معين
     */
    public function hasTeamSubmission(int $teamId): bool
    {
        return $this->submissions()
            ->where('team_id', $teamId)
            ->exists();
    }

    /**
     * جلب تسليم فريق معين
     */
    public function getTeamSubmission(int $teamId)
    {
        return $this->submissions()
            ->where('team_id', $teamId)
            ->first();
    }

    /**
     * النطاقات
     */
    public function scopeWithSubmissionsForTeam($query, int $teamId)
    {
        return $query->with(['submissions' => function($q) use ($teamId) {
            $q->where('team_id', $teamId);
        }]);
    }

    /**
     * الوظائف المساعدة
     */
    public function getSubmissionStatusForTeam(int $teamId): string
    {
        $submission = $this->getTeamSubmission($teamId);

        if (!$submission) {
            return 'not_submitted';
        }

        if ($submission->graded_at) {
            return 'graded';
        }

        return 'pending';
    }

    public function getSubmissionScoreForTeam(int $teamId): ?float
    {
        $submission = $this->getTeamSubmission($teamId);
        return $submission?->score;
    }
}