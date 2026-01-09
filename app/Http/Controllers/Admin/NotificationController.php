<?php

namespace App\Http\Controllers\Admin;

use App\Model\Order;
use App\Model\Conversation;
use App\Model\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Foundation\Application;

class NotificationController extends Controller
{
    public function __construct(
        private Notification $notification,
        private Conversation $conversation,
        private Order $order
    )
    {}

    /**
     * Display notification list
     */
    function index(Request $request): View|Factory|Application
    {
        $queryParam = [];
        $search = $request['search'];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $notifications = $this->notification->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%");
                }
            });
            $queryParam = ['search' => $request['search']];
        } else {
            $notifications = $this->notification;
        }

        $notifications = $notifications->latest()->paginate(Helpers::getPagination())->appends($queryParam);
        return view('admin-views.notification.index', compact('notifications', 'search'));
    }

    /**
     * Store new notification
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|max:100',
            'description' => 'required|max:255'
        ], [
            'title.max' => translate('Title is too long!'),
            'description.max' => translate('Description is too long!'),
        ]);

        $notification = $this->notification;
        $notification->title = $request->title;
        $notification->description = $request->description;
        $notification->image = Helpers::upload('notification/', 'png', $request->file('image'));
        $notification->status = 1;
        $notification->user_id = auth()->id();
        $notification->notification_type = 'admin_notification';
        $notification->is_read = false;
        $notification->save();

        $notification->image = asset('storage/app/public/notification') . '/' . $notification->image;

        try {
            Helpers::send_push_notif_to_topic($notification, 'notify', 'general');
        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification failed!'));
        }

        Toastr::success(translate('Notification sent successfully!'));
        return back();
    }

    /**
     * Edit notification
     */
    public function edit($id): Renderable
    {
        $notification = $this->notification->find($id);
        return view('admin-views.notification.edit', compact('notification'));
    }

    /**
     * Update notification
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required|max:100',
            'description' => 'required|max:255'
        ], [
            'title.max' => translate('Title is too long!'),
            'description.max' => translate('Description is too long!'),
        ]);

        $notification = $this->notification->find($id);
        $notification->title = $request->title;
        $notification->description = $request->description;
        $notification->image = $request->has('image') ? Helpers::update('notification/', $notification->image, 'png', $request->file('image')) : $notification->image;
        $notification->save();

        Toastr::success(translate('Notification updated successfully!'));
        return back();
    }

    /**
     * Update notification status
     */
    public function status(Request $request): RedirectResponse
    {
        $notification = $this->notification->find($request->id);
        $notification->status = $request->status;
        $notification->save();

        Toastr::success(translate('Notification status updated!'));
        return back();
    }

    /**
     * Delete notification
     */
    public function delete(Request $request): RedirectResponse
    {
        $notification = $this->notification->find($request->id);
        Helpers::delete('notification/' . $notification['image']);
        $notification->delete();

        Toastr::success(translate('Notification removed!'));
        return back();
    }

    /**
     * Get notification counts (AJAX endpoint)
     * Called by frontend every 10 seconds
     */
    public function getNotificationCount()
    {
        try {
            // Get unread message count (distinct users)
            $messageCount = $this->conversation
                ->where('checked', 0)
                ->distinct('user_id')
                ->count();
            
            // Get pending order count (last 7 days)
            $orderCount = $this->order
                ->where('order_status', 'pending')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->count();
            
            return response()->json([
                'success' => true,
                'message_count' => $messageCount,
                'order_count' => $orderCount,
                'timestamp' => now()->toIso8601String()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Notification count error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching notification counts',
                'message_count' => 0,
                'order_count' => 0
            ], 500);
        }
    }

    /**
     * Mark all messages as read
     * Called when admin visits message page
     */
    public function markMessagesAsRead()
    {
        try {
            // Update all unread conversations
            $updated = $this->conversation
                ->where('checked', 0)
                ->update(['checked' => 1]);
            
            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'updated_count' => $updated
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mark messages read error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error marking messages as read'
            ], 500);
        }
    }

    /**
     * Mark orders notification as read
     * Called when admin visits orders page
     */
    public function markOrdersAsRead()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Orders notification cleared'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mark orders read error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error clearing orders notification'
            ], 500);
        }
    }

    /**
     * Get detailed notification data
     * Optional: For notification dropdown panel
     */
    public function getNotificationDetails()
    {
        try {
            // Get recent unread messages with user details
            $recentMessages = $this->conversation
                ->where('checked', 0)
                ->with('user:id,f_name,l_name,image')
                ->select('id', 'user_id', 'message', 'created_at')
                ->latest()
                ->take(5)
                ->get()
                ->map(function($conversation) {
                    return [
                        'id' => $conversation->id,
                        'user_name' => $conversation->user ? 
                            $conversation->user->f_name . ' ' . $conversation->user->l_name : 
                            'Unknown User',
                        'user_image' => $conversation->user && $conversation->user->image ? 
                            asset('storage/app/public/profile/' . $conversation->user->image) : 
                            asset('public/assets/admin/img/default-user.png'),
                        'message' => Str::limit($conversation->message, 50),
                        'time' => $conversation->created_at->diffForHumans()
                    ];
                });
            
            // Get recent pending orders
            $recentOrders = $this->order
                ->where('order_status', 'pending')
                ->with('customer:id,f_name,l_name')
                ->select('id', 'user_id', 'order_amount', 'created_at')
                ->latest()
                ->take(5)
                ->get()
                ->map(function($order) {
                    return [
                        'id' => $order->id,
                        'customer_name' => $order->customer ? 
                            $order->customer->f_name . ' ' . $order->customer->l_name : 
                            'Guest',
                        'amount' => Helpers::currency_converter($order->order_amount),
                        'time' => $order->created_at->diffForHumans(),
                        'url' => route('admin.orders.details', ['id' => $order->id])
                    ];
                });
            
            return response()->json([
                'success' => true,
                'messages' => [
                    'count' => $this->conversation->where('checked', 0)->distinct('user_id')->count(),
                    'recent' => $recentMessages
                ],
                'orders' => [
                    'count' => $this->order->where('order_status', 'pending')->count(),
                    'recent' => $recentOrders
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Notification details error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching notification details'
            ], 500);
        }
    }
}