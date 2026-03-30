<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraduationProject extends Model
{
    protected $table='team_projects';
    protected $fillable = ['academic_year_id','team_id','proposal_id',	'final_score','image_url'];
}
