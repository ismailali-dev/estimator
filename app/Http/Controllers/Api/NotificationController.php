<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends ApiBaseController
{
    /**
     * Get all notifications for the authenticated user.
     */
   public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->select('id','created_at', 'data', 'read_at')
            ->orderBy('created_at', 'desc')
            ->paginate(10) // Fetch 10 notifications per page
            ->through(function ($notification) {
                return [
                    'id'=>$notification->id,
                    'title' => $notification->data['title'] ?? null,
                    'body' => $notification->data['body'] ?? null,
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at,
                ];
            });
    
    
        $this->response_data["data"] = [
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(), // Total pages
                'total_items' => $notifications->total(),
                'per_page' => $notifications->perPage(),
            ]
        ];
        
        $this->response_data["message"] = "Notification List Retrieved";
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
        
        
       
    }
    /**
     * Mark selected notifications as read.
     */
    public function markAsRead(Request $request)
    {
        $user = $request->user();

        // Validate the incoming request
        $request->validate([
            'notification_ids' => 'array|required',
            'notification_ids.*' => 'string|exists:notifications,id',
        ]);

        // Retrieve the notifications
        $notifications = $user->notifications()->whereIn('id', $request->notification_ids)->get();

        // Mark them as read
        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notifications marked as read successfully.',
        ]);
    }
}

