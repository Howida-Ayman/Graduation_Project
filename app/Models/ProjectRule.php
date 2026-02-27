<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRule extends Model
{
    protected $table='project_rules';
    protected $fillable = [
        'min_team_size',
        'max_team_size',
        'team_formation_deadline'
    ];
}
