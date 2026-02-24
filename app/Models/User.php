<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'national_id',
        'password',
        'full_name',
        'email',
        'phone',
        'track_name',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function staffprofile()
    {
        return $this->hasOne(StaffProfile::class);
    }
    public function studentprofile()
    {
        return $this->hasOne(StudentProfile::class);
    }

public function role()
{
    return $this->belongsTo(Role::class);
}

    public function supervisedTeams()
    {
        return $this->belongsToMany(
            Team::class,
            'team_supervisors',
            'supervisor_user_id',
            'team_id'
        )->withPivot(['supervisor_role', 'assigned_at', 'ended_at'])
         ->wherePivot('ended_at', null);  // الفرق اللي لسه بيشرف عليها
    }



}
