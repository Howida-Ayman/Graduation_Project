<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * جلب محادثة الفريق الحالي للمستخدم
     */
    public function getTeamConversation(Request $request)
    {
        $user = $request->user();
        
        // جلب الفريق الحالي للمستخدم
        $team = $user->currentTeam();
        
        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 404);
        }
        
        // جلب أو إنشاء المحادثة
        $conversation = $team->conversation;
        
        if (!$conversation) {
            $conversation = ChatService::createTeamChat($team);
        }
        
        // تحديث المشاركين لو فيه تغييرات
        ChatService::syncTeamChatParticipants($team);
        
        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation->load(['participants.user', 'messages.sender']))
        ]);
    }
    
    /**
     * إرسال رسالة في الشات
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        
        // تأكد أن المستخدم مشارك في المحادثة
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this conversation'
            ], 403);
        }
        
        $request->validate([
            'message' => 'required_without:attachment|string|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);
        
        DB::beginTransaction();
        
        try {
            $messageData = [
                'conversation_id' => $conversation->id,
                'sender_user_id' => $user->id,
                'message' => $request->message ?? '',
                'type' => 'text',
            ];
            
            // لو فيه مرفق
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/chat'), $fileName);
                
                $messageData['file_url'] = 'uploads/chat/' . $fileName;
                $messageData['type'] = in_array($file->getClientOriginalExtension(), ['jpg','jpeg','png','gif']) 
                    ? 'image' : 'file';
            }
            
            $message = Message::create($messageData);
            
            DB::commit();
            
            // Broadcast event (for real-time)
            // event(new NewMessageEvent($message));
            
            return response()->json([
                'success' => true,
                'message' => 'Message sent',
                'data' => new MessageResource($message->load('sender'))
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * جلب رسائل المحادثة مع Pagination
     */
    public function getMessages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return response()->json([
            'success' => true,
            'data' => MessageResource::collection($messages)
        ]);
    }
    
    /**
     * تحديث حالة قراءة الرسائل
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        
        $unreadMessages = $conversation->messages()
            ->where('sender_user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();
        
        foreach ($unreadMessages as $message) {
            $message->markAsRead($user->id);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
    }
}