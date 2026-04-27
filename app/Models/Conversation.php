// app/Models/Conversation.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
    ];

    // العلاقات
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    // Scopes
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    // Helper methods
    public function addParticipant($userId, $role = 'member')
    {
        return $this->participants()->updateOrCreate(
            ['user_id' => $userId],
            [
                'role' => $role,
                'joined_at' => now(),
                'left_at' => null,
            ]
        );
    }

    public function removeParticipant($userId)
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->update(['left_at' => now()]);
    }

    public function hasParticipant($userId)
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    public function getUnreadCountForUser($userId)
    {
        return $this->messages()
            ->whereDoesntHave('reads', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('sender_user_id', '!=', $userId)
            ->count();
    }
}