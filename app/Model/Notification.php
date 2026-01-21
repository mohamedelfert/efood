<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'status' => 'integer',
        'is_read' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'user_id',
        'branch_id',
        'title',
        'description',
        'notification_type',
        'reference_id',
        'status',
        'is_read',
        'image',
        'data'
    ];

    /**
     * Get active notifications
     */
    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    /**
     * Get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Get broadcast notifications (sent to all users)
     */
    public function scopeBroadcast($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('user_id')->orWhere('user_id', 0);
        });
    }

    /**
     * Get user-specific notifications
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get notifications for specific branch (including global)
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        });
    }

    /**
     * Get global notifications (for all branches)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('branch_id');
    }

    /**
     * Get notifications by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Check if notification is broadcast
     */
    public function isBroadcast(): bool
    {
        return empty($this->user_id) || $this->user_id == 0;
    }

    /**
     * Check if notification is global (all branches)
     */
    public function isGlobal(): bool
    {
        return is_null($this->branch_id);
    }

    /**
     * Check if notification is available for specific branch
     */
    public function isAvailableForBranch($branchId): bool
    {
        return is_null($this->branch_id) || $this->branch_id == $branchId;
    }

    /**
     * Get full image path
     */
    public function getImageFullPathAttribute(): string
    {
        $image = $this->image ?? null;
        $path = asset('public/assets/admin/img/icons/upload_img2.png');

        if (!is_null($image)) {
            if (str_starts_with($image, 'static:')) {
                $iconName = substr($image, 7); // Remove 'static:' prefix
                if (file_exists(public_path("assets/admin/img/icons/{$iconName}"))) {
                    return asset("public/assets/admin/img/icons/{$iconName}");
                }
            } elseif (Storage::disk('public')->exists('notification/' . $image)) {
                $path = asset('storage/app/public/notification/' . $image);
            }
        }
        return $path;
    }

    /**
     * Get formatted created at date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Relationship: User who owns this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    /**
     * Relationship: Branch that owns this notification
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Mark this notification as read
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark this notification as unread
     */
    public function markAsUnread(): bool
    {
        return $this->update(['is_read' => false]);
    }

    /**
     * Static method to create a notification
     */
    public static function createNotification(array $data): self
    {
        return self::create([
            'user_id' => $data['user_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'notification_type' => $data['type'] ?? 'general',
            'reference_id' => $data['reference_id'] ?? null,
            'image' => $data['image'] ?? null,
            'is_read' => false,
            'status' => 1,
            'data' => $data['data'] ?? null,
        ]);
    }

    /**
     * Static method to send broadcast notification
     */
    public static function broadcast(array $data): self
    {
        return self::createNotification(array_merge($data, [
            'user_id' => null,
            'branch_id' => null
        ]));
    }

    /**
     * Static method to send branch-specific notification
     */
    public static function sendToBranch($branchId, array $data): self
    {
        return self::createNotification(array_merge($data, [
            'branch_id' => $branchId,
            'user_id' => null
        ]));
    }
}