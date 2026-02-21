<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /*
    |-----------------------------------------
    | Relationships
    |-----------------------------------------
    */


    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function previousProjects()
    {
        return $this->hasMany(PreviousProject::class);
    }
}