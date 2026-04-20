<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DatabaseNotification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc');
        
        // فلترة حسب النوع (All, Unread, Read)
        if ($request->filter == 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->filter == 'read') {
            $query->whereNotNull('read_at');
        }
        
        $notifications = $query->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $notifications->map(function($notification) {
                $data = $notification->data;
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            }),
            'unread_count' => DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->whereNull('read_at')
                ->count(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ]
        ]);
    }
    


public function markAsRead($id)
{
    $user = request()->user();

    $notification = DatabaseNotification::where('id', $id)
        ->where('notifiable_id', $user->id)
        ->where('notifiable_type', 'App\\Models\\User') // ✅ أضفنا الشرط ده
        ->first();

    if (!$notification) {
        return response()->json([
            'success' => false,
            'message' => 'Notification not found'
        ], 404);
    }

    $notification->update(['read_at' => now()]);

    return response()->json([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
}
    
    public function markAllAsRead()
    {
        $user = request()->user();
        
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
    
    public function destroy($id)
    {
        $user = request()->user();
        $notification = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();
        
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }
}