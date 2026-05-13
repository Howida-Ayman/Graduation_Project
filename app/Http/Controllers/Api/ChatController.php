<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TeamMembership;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * جلب محادثة الفريق الحالي
     */
    public function getTeamConversation(Request $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->first();

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found'
            ], 404);
        }

        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in any team'
            ], 404);
        }

        $team = $membership->team;

        // جلب أو إنشاء المحادثة
        $conversation = $team->conversation;

        if (!$conversation) {
            $conversation = ChatService::createTeamChat($team);
        } else {
            ChatService::syncTeamChatParticipants($team);
            $conversation->refresh();
        }

        // تحميل العلاقات
        $conversation->load([
            'participants.user',
            'messages.sender',
            'messages.reads'
        ]);

        $messages = $conversation->messages()->with('sender')->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => [
                    'id' => $conversation->id,
                    'name' => $conversation->name,
                    'participants' => $conversation->participants->map(function ($participant) {
                        return [
                            'user_id' => $participant->user_id,
                            'name' => $participant->user?->full_name,
                            'email' => $participant->user?->email,
                            'profile_image_url' => $participant->user?->profile_image_url ? asset($participant->user->profile_image_url) : null,
                            'role' => $participant->role,
                            'joined_at' => $participant->joined_at,
                        ];
                    }),
                ],
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ]
            ]
        ]);
    }

    /**
     * إرسال رسالة جديدة
     */
    public function sendMessage(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'message' => 'required_without:attachment|string|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::find($request->conversation_id);

        // التأكد من أن المستخدم مشارك في المحادثة
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this conversation'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $messageData = [
                'conversation_id' => $conversation->id,
                'sender_user_id' => $user->id,
                'message' => $request->message ?? '',
                'type' => 'text',
            ];

            // مرفق
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/chat'), $fileName);

                $messageData['file_url'] = 'uploads/chat/' . $fileName;
                $messageData['type'] = in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif']) 
                    ? 'image' : 'file';
            }

            $message = Message::create($messageData);

            DB::commit();

            \Log::info('About to broadcast message ID: ' . $message->id);
try {
    broadcast(new NewMessageEvent($message, $conversation->id))->toOthers();
    \Log::info('Broadcast successful');
} catch (\Exception $e) {
    \Log::error('Broadcast error: ' . $e->getMessage());
}

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'type' => $message->type,
                    'file_url' => $message->file_url ? asset($message->file_url) : null,
                    'sender' => [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'profile_image_url' => $user->profile_image_url ? asset($user->profile_image_url) : null,
                    ],
                    'created_at' => $message->created_at->toDateTimeString(),
                    'is_mine' => true,
                ]
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
     * تحديث حالة قراءة الرسائل
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $unreadMessages = $conversation->messages()
            ->where('sender_user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markAsRead($user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
            'count' => $unreadMessages->count()
        ]);
    }
}