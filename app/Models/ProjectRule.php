<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRule extends Model
{
    protected $table = 'project_rules';

    protected $fillable = [
        'min_team_size',
        'max_team_size',
        'project1_team_formation_deadline',
        'supervisor_max_score',
        'defense_max_score',
        'passing_percentage',
    ];

    protected $casts = [
        'team_formation_deadline' => 'date',
    ];

    public static function getCurrent()
    {
        return self::first();
    }

    public static function getMinTeamSize()
    {
        return self::first()?->min_team_size ?? 4;
    }

    public static function getMaxTeamSize()
    {
        return self::first()?->max_team_size ?? 6;
    }

    public static function getTeamFormationDeadline()
    {
        return self::first()?->team_formation_deadline;
    }
}