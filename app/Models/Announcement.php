<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
protected $fillable = [
    'academic_year_id',
    'team_id',
    'sent_by_user_id',
    'message',
];
public function academicYear()
{
    return $this->belongsTo(AcademicYear::class);
}

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
