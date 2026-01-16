<?php

namespace App\Http\Controllers\Admin;

use App\Model\Order;
use App\Model\Branch;
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
        private Order $order,
        private Branch $branch
    )
    {}

    /**
     * Display notification list with search and branch filter
     */
    function index(Request $request): View|Factory|Application
    {
        $queryParam = [];
        $search = $request['search'];
        $branchFilter = $request['branch_id'] ?? 'all';

        $query = $this->notification->with('branch');

        // Search functionality
        if ($request->has('search') && !empty($search)) {
            $key = explode(' ', $request['search']);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%");
                }
            });
            $queryParam['search'] = $request['search'];
        }

        // Branch filter functionality
        if ($request->has('branch_id') && $request['branch_id'] != 'all') {
            $query->where(function ($q) use ($branchFilter) {
                $q->where('branch_id', $branchFilter)
                  ->orWhereNull('branch_id'); // Include global notifications
            });
            $queryParam['branch_id'] = $request['branch_id'];
        }

        $notifications = $query->latest()->paginate(Helpers::getPagination())->appends($queryParam);
        $branches = $this->branch->active()->get();

        return view('admin-views.notification.index', compact('notifications', 'search', 'branches', 'branchFilter'));
    }

    /**
     * Store new notification
     */
    public function store(Request $request): RedirectResponse
    {
        // Custom validation rules
        $rules = [
            'title' => 'required|max:100',
            'description' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
        ];

        // Only validate branch_id if it's not 'all'
        if ($request->branch_id != 'all' && !empty($request->branch_id)) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $messages = [
            'title.required' => translate('Notification title is required'),
            'title.max' => translate('Title is too long!'),
            'description.required' => translate('Notification description is required'),
            'description.max' => translate('Description is too long!'),
            'branch_id.exists' => translate('Selected branch is invalid'),
            'image.image' => translate('File must be an image'),
            'image.mimes' => translate('Image must be jpg, jpeg, png or gif'),
            'image.max' => translate('Image size must not exceed 2MB')
        ];

        $request->validate($rules, $messages);

        try {
            $notification = $this->notification;
            $notification->branch_id = ($request->branch_id == 'all' || empty($request->branch_id)) ? null : $request->branch_id;
            $notification->title = $request->title;
            $notification->description = $request->description;
            $notification->image = $request->hasFile('image') ? Helpers::upload('notification/', 'png', $request->file('image')) : null;
            $notification->status = 1;
            $notification->user_id = auth()->id();
            $notification->notification_type = 'admin_notification';
            $notification->is_read = false;
            $notification->save();

            // Prepare notification data for push
            $notificationData = $notification->fresh();
            $notificationData->image = $notification->image ? 
                asset('storage/app/public/notification/' . $notification->image) : null;

            // Send push notification
            try {
                $topic = is_null($notification->branch_id) ? 'general' : 'branch_' . $notification->branch_id;
                Helpers::send_push_notif_to_topic($notificationData, 'notify', $topic);
            } catch (\Exception $e) {
                Log::error('Push notification failed: ' . $e->getMessage());
                Toastr::warning(translate('Push notification failed!'));
            }

            $successMessage = is_null($notification->branch_id) 
                ? translate('Notification sent to all branches successfully!')
                : translate('Notification sent to selected branch successfully!');
            
            Toastr::success($successMessage);
            return redirect()->route('admin.notification.add-new');

        } catch (\Exception $e) {
            Log::error('Notification creation failed: ' . $e->getMessage());
            Toastr::error(translate('Failed to send notification. Please try again.'));
            return back()->withInput();
        }
    }

    /**
     * Edit notification
     */
    public function edit($id): Renderable
    {
        $notification = $this->notification->with('branch')->findOrFail($id);
        $branches = $this->branch->active()->get();
        return view('admin-views.notification.edit', compact('notification', 'branches'));
    }

    /**
     * Update notification
     */
    public function update(Request $request, $id): RedirectResponse
    {
        // Custom validation rules
        $rules = [
            'title' => 'required|max:100',
            'description' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
        ];

        // Only validate branch_id if it's not 'all'
        if ($request->branch_id != 'all' && !empty($request->branch_id)) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $messages = [
            'title.required' => translate('Notification title is required'),
            'title.max' => translate('Title is too long!'),
            'description.required' => translate('Notification description is required'),
            'description.max' => translate('Description is too long!'),
            'branch_id.exists' => translate('Selected branch is invalid'),
            'image.image' => translate('File must be an image'),
            'image.mimes' => translate('Image must be jpg, jpeg, png or gif'),
            'image.max' => translate('Image size must not exceed 2MB')
        ];

        $request->validate($rules, $messages);

        try {
            $notification = $this->notification->findOrFail($id);
            $notification->branch_id = ($request->branch_id == 'all' || empty($request->branch_id)) ? null : $request->branch_id;
            $notification->title = $request->title;
            $notification->description = $request->description;
            $notification->image = $request->hasFile('image') ? 
                Helpers::update('notification/', $notification->image, 'png', $request->file('image')) : 
                $notification->image;
            $notification->save();

            Toastr::success(translate('Notification updated successfully!'));
            return redirect()->route('admin.notification.add-new');

        } catch (\Exception $e) {
            Log::error('Notification update failed: ' . $e->getMessage());
            Toastr::error(translate('Failed to update notification. Please try again.'));
            return back()->withInput();
        }
    }

    /**
     * Update notification status
     */
    public function status(Request $request): RedirectResponse
    {
        try {
            $notification = $this->notification->findOrFail($request->id);
            $notification->status = $request->status;
            $notification->save();

            Toastr::success(translate('Notification status updated!'));
            return back();
        } catch (\Exception $e) {
            Log::error('Status update failed: ' . $e->getMessage());
            Toastr::error(translate('Failed to update status. Please try again.'));
            return back();
        }
    }

    /**
     * Delete notification
     */
    public function delete(Request $request): RedirectResponse
    {
        try {
            $notification = $this->notification->findOrFail($request->id);
            
            if ($notification->image) {
                Helpers::delete('notification/' . $notification->image);
            }
            
            $notification->delete();

            Toastr::success(translate('Notification removed!'));
            return back();
        } catch (\Exception $e) {
            Log::error('Notification deletion failed: ' . $e->getMessage());
            Toastr::error(translate('Failed to delete notification. Please try again.'));
            return back();
        }
    }

    /**
     * Get notification counts (AJAX endpoint)
     * Called by frontend every 10 seconds
     */
    public function getNotificationCount(Request $request)
    {
        try {
            $branchId = $request->input('branch_id');
            
            // Get unread message count (distinct users)
            $messageCount = $this->conversation
                ->where('checked', 0)
                ->distinct('user_id')
                ->count();
            
            // Get pending order count (last 7 days)
            $orderQuery = $this->order
                ->where('order_status', 'pending')
                ->whereDate('created_at', '>=', now()->subDays(7));
            
            // Filter by branch if provided
            if ($branchId && $branchId != 'all') {
                $orderQuery->where('branch_id', $branchId);
            }
            
            $orderCount = $orderQuery->count();
            
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
    public function getNotificationDetails(Request $request)
    {
        try {
            $branchId = $request->input('branch_id');
            
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
            $orderQuery = $this->order
                ->where('order_status', 'pending')
                ->with('customer:id,f_name,l_name')
                ->select('id', 'user_id', 'order_amount', 'branch_id', 'created_at')
                ->latest()
                ->take(5);
            
            // Filter by branch if provided
            if ($branchId && $branchId != 'all') {
                $orderQuery->where('branch_id', $branchId);
            }
            
            $recentOrders = $orderQuery->get()
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
                    'count' => $branchId && $branchId != 'all' 
                        ? $this->order->where('order_status', 'pending')->where('branch_id', $branchId)->count()
                        : $this->order->where('order_status', 'pending')->count(),
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