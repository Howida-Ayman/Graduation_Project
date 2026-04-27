// app/Services/ChatService.php
<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;

class ChatService
{
    /**
     * إنشاء شات جماعي للفريق تلقائياً
     */
    public static function createTeamChat(Team $team)
    {
        // اسم الجروب
        $chatName = "فريق " . ($team->name ?? "Team #{$team->id}");
        
        // إنشاء المحادثة
        $conversation = Conversation::create([
            'team_id' => $team->id,
            'name' => $chatName,
        ]);
        
        // إضافة جميع المشاركين
        $allUsers = $team->getAllUsers();
        
        foreach ($allUsers as $user) {
            $role = 'member';
            
            // لو المستخدم دكتور أو معيد، يكون admin في الشات
            if (in_array($user->role?->code, ['doctor', 'TA', 'ta'])) {
                $role = 'admin';
            }
            
            $conversation->addParticipant($user->id, $role);
        }
        
        return $conversation;
    }
    
    /**
     * تحديث المشاركين في الشات (عند إضافة/إزالة عضو)
     */
    public static function syncTeamChatParticipants(Team $team)
    {
        $conversation = $team->conversation;
        
        if (!$conversation) {
            return self::createTeamChat($team);
        }
        
        $currentParticipantIds = $conversation->participants()
            ->whereNull('left_at')
            ->pluck('user_id')
            ->toArray();
        
        $expectedUserIds = $team->getAllUsers()->pluck('id')->toArray();
        
        // إضافة الجدد
        $newUserIds = array_diff($expectedUserIds, $currentParticipantIds);
        foreach ($newUserIds as $userId) {
            $user = User::find($userId);
            $role = in_array($user->role?->code, ['doctor', 'TA', 'ta']) ? 'admin' : 'member';
            $conversation->addParticipant($userId, $role);
        }
        
        // إزالة اللي مش موجودين (optional: نخليهم left)
        $removedUserIds = array_diff($currentParticipantIds, $expectedUserIds);
        foreach ($removedUserIds as $userId) {
            $conversation->removeParticipant($userId);
        }
        
        return $conversation;
    }
}