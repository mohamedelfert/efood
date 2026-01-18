<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get notifications for authenticated user
     * Includes: user-specific + admin broadcast notifications
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'User not authenticated']]
            ], 401);
        }

        // Get notifications with branch eager loaded
        $userNotifications = Notification::with('branch')
            ->where('user_id', $user->id)
            ->active()
            ->latest()
            ->get();

        $broadcastNotifications = Notification::with('branch')
            ->whereNull('user_id')
            ->orWhere('user_id', 0)
            ->active()
            ->latest()
            ->get();

        // Merge and sort by created_at
        $allNotifications = $userNotifications
            ->merge($broadcastNotifications)
            ->sortByDesc('created_at')
            ->values();

        // Transform notifications
        $notifications = $this->transformNotifications($allNotifications);

        // Count unread notifications
        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'total_size' => $notifications->count(),
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ], 200);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id')
                    ->orWhere('user_id', 0);
            })
            ->first();

        if (!$notification) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Notification not found']]
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ], 200);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        // Mark user-specific notifications as read
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ], 200);
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id) // Only user's own notifications
            ->first();

        if (!$notification) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Notification not found']]
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ], 200);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $userUnread = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->active()
            ->count();

        $broadcastUnread = Notification::whereNull('user_id')
            ->orWhere('user_id', 0)
            ->where('is_read', false)
            ->active()
            ->count();

        $totalUnread = $userUnread + $broadcastUnread;

        return response()->json([
            'unread_count' => $totalUnread,
            'user_notifications' => $userUnread,
            'broadcast_notifications' => $broadcastUnread
        ], 200);
    }

    /**
     * Get notifications by type
     */
    public function getNotificationsByType(Request $request, $type): JsonResponse
    {
        $user = $request->user();

        $userNotifications = Notification::with('branch')
            ->where('user_id', $user->id)
            ->where('notification_type', $type)
            ->active()
            ->latest()
            ->get();

        $broadcastNotifications = Notification::with('branch')
            ->whereNull('user_id')
            ->orWhere('user_id', 0)
            ->where('notification_type', $type)
            ->active()
            ->latest()
            ->get();

        $allNotifications = $userNotifications
            ->merge($broadcastNotifications)
            ->sortByDesc('created_at')
            ->values();

        $notifications = $this->transformNotifications($allNotifications);

        return response()->json([
            'type' => $type,
            'total_size' => $notifications->count(),
            'notifications' => $notifications
        ], 200);
    }

    /**
     * Helper to transform notification collection
     */
    private function transformNotifications($notifications)
    {
        return $notifications->map(function ($notification) {
            $branchDetails = null;
            if ($notification->branch_id && $notification->branch) {
                $branchDetails = [
                    'name' => $notification->branch->name,
                    'coupons' => $notification->branch->activeCoupons()
                ];
            }

            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'description' => $notification->description,
                'image' => $notification->image_full_path ?? null,
                'notification_type' => $notification->notification_type ?? 'general',
                'reference_id' => $notification->reference_id ?? null,
                'branch_id' => $notification->branch_id,
                'branch_details' => $branchDetails,
                'is_read' => $notification->is_read ?? false,
                'is_broadcast' => empty($notification->user_id) || $notification->user_id == 0,
                'created_at' => $notification->created_at,
                'data' => $notification->data ?? null,
            ];
        });
    }

    /**
     * Clear all notifications (soft delete or mark as read)
     */
    public function clearAllNotifications(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete user-specific notifications
        Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'All notifications cleared successfully'
        ], 200);
    }
}