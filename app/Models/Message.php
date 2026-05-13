<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'message',
        'type',
        'file_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // العلاقات
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    // Helper methods
    public function markAsRead($userId)
    {
        return $this->reads()->updateOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }

    public function isReadByUser($userId)
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }
}