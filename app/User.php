<?php

namespace App;

use App\Model\Order;
use App\Model\Branch;
use App\Model\Review;
use App\Model\DMReview;
use App\Model\Wishlist;
use App\Model\ChefBranch;
use App\Models\BranchReview;
use App\Model\Notification;
use Illuminate\Support\Str;
use App\Models\ServiceReview;
use App\Models\PaymentMethod;
use App\Model\CustomerAddress;
use App\Models\WalletBonusUser;
use App\Model\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'name', 'name', 'phone', 'email', 'password', 'point', 'is_active', 'user_type', 'refer_code', 'refer_by', 'language_code'
    // ];

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_phone_verified' => 'integer',
        'point' => 'integer',
        'wallet_balance' => 'float',
        'is_active' => 'boolean',
    ];

    // ===========================
    // RELATIONSHIPS
    // ===========================
    
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'user_id');
    }

    public function chefBranch(): HasOne
    {
        return $this->hasOne(ChefBranch::class, 'user_id', 'id');
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class)->where('is_active', true);
    }

    public function defaultPaymentMethod()
    {
        return $this->hasOne(PaymentMethod::class)->where('is_default', true);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('created_at', 'desc');
    }

    public function walletBonusUsers()
    {
        return $this->hasMany(WalletBonusUser::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id')->orderBy('created_at', 'desc');
    }

    // ===========================
    // SCOPES
    // ===========================
    
    public function scopeOfType($query, $user_type)
    {
        if ($user_type != 'customer') {
            return $query->where('user_type', $user_type);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeWithFcmToken($query)
    {
        return $query->whereNotNull('cm_firebase_token')
                     ->where('cm_firebase_token', '!=', '');
    }

    // ===========================
    // FCM TOKEN METHODS
    // ===========================
    
    /**
     * Check if user has valid FCM token
     */
    public function hasFcmToken(): bool
    {
        return !empty($this->cm_firebase_token) && strlen($this->cm_firebase_token) > 20;
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(string $token): bool
    {
        $this->cm_firebase_token = $token;
        $saved = $this->save();

        if ($saved) {
            Log::info('FCM token updated', [
                'user_id' => $this->id,
                'user_type' => $this->user_type,
                'token_preview' => substr($token, 0, 20) . '...'
            ]);
        }

        return $saved;
    }

    /**
     * Clear FCM token (on logout)
     */
    public function clearFcmToken(): bool
    {
        $oldToken = $this->cm_firebase_token;
        $this->cm_firebase_token = null;
        $saved = $this->save();

        if ($saved && $oldToken) {
            Log::info('FCM token cleared', [
                'user_id' => $this->id,
                'token_preview' => substr($oldToken, 0, 20) . '...'
            ]);
        }

        return $saved;
    }

    /**
     * Send push notification to this user
     */
    public function sendPushNotification(array $data): bool
    {
        if (!$this->hasFcmToken()) {
            Log::warning('Cannot send push notification - no FCM token', [
                'user_id' => $this->id
            ]);
            return false;
        }

        try {
            $notificationData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? $data['body'] ?? '',
                'image' => $data['image'] ?? '',
                'order_id' => $data['order_id'] ?? '',
                'type' => $data['type'] ?? 'general'
            ];

            $result = \App\CentralLogics\Helpers::send_push_notif_to_device(
                $this->cm_firebase_token,
                $notificationData,
                $data['is_deliveryman_assigned'] ?? false
            );

            Log::info('Push notification sent to user', [
                'user_id' => $this->id,
                'type' => $data['type'] ?? 'general',
                'result' => $result
            ]);

            return $result !== false;

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ===========================
    // NOTIFICATION METHODS
    // ===========================
    
    /**
     * Get user's unread notifications count
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return Notification::forUser($this->id)
            ->unread()
            ->active()
            ->count();
    }

    /**
     * Get user's unread notifications count (method)
     */
    public function getUnreadNotificationsCount(): int
    {
        return $this->unread_notifications_count;
    }

    /**
     * Create in-app notification for this user
     */
    public function createNotification(array $data): Notification
    {
        return Notification::createNotification(array_merge($data, [
            'user_id' => $this->id
        ]));
    }

    /**
     * Send complete notification (Push + In-App)
     */
    public function notify(array $data): array
    {
        $results = [
            'push' => false,
            'in_app' => false
        ];

        try {
            // Create in-app notification
            $notification = $this->createNotification([
                'title' => $data['title'],
                'description' => $data['description'] ?? $data['body'] ?? '',
                'type' => $data['type'] ?? 'general',
                'reference_id' => $data['reference_id'] ?? null,
                'image' => $data['image'] ?? null,
                'data' => $data['data'] ?? null,
            ]);
            $results['in_app'] = true;

            // Send push notification if user has FCM token
            if ($this->hasFcmToken()) {
                $results['push'] = $this->sendPushNotification($data);
            }

            Log::info('Complete notification sent', [
                'user_id' => $this->id,
                'type' => $data['type'] ?? 'general',
                'push_sent' => $results['push'],
                'in_app_created' => $results['in_app']
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to send complete notification', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $results;
        }
    }

    /**
     * Mark all user's notifications as read
     */
    public function markAllNotificationsAsRead(): int
    {
        return Notification::forUser($this->id)
            ->unread()
            ->update(['is_read' => true]);
    }

    // ===========================
    // WALLET METHODS
    // ===========================
    
    public function hasWalletBalance(float $amount): bool
    {
        return $this->wallet_balance >= $amount;
    }

    public function getFormattedWalletBalanceAttribute(): string
    {
        return number_format($this->wallet_balance, 2);
    }

    /**
     * Add funds to wallet
     */
    public function addWalletBalance(float $amount, string $type = 'top_up', ?string $reference = null): bool
    {
        $this->wallet_balance += $amount;
        $saved = $this->save();

        if ($saved) {
            Log::info('Wallet balance added', [
                'user_id' => $this->id,
                'amount' => $amount,
                'type' => $type,
                'new_balance' => $this->wallet_balance
            ]);
        }

        return $saved;
    }

    /**
     * Deduct funds from wallet
     */
    public function deductWalletBalance(float $amount, string $type = 'payment', ?string $reference = null): bool
    {
        if (!$this->hasWalletBalance($amount)) {
            Log::warning('Insufficient wallet balance', [
                'user_id' => $this->id,
                'required' => $amount,
                'available' => $this->wallet_balance
            ]);
            return false;
        }

        $this->wallet_balance -= $amount;
        $saved = $this->save();

        if ($saved) {
            Log::info('Wallet balance deducted', [
                'user_id' => $this->id,
                'amount' => $amount,
                'type' => $type,
                'new_balance' => $this->wallet_balance
            ]);
        }

        return $saved;
    }

    // ===========================
    // IMAGE METHODS
    // ===========================
    
    public function getImageFullPathAttribute(): string
    {
        $image = $this->image ?? null;
        $path = asset('public/assets/admin/img/160x160/img1.jpg');

        if (!is_null($image)) {
            if ($this->user_type == 'kitchen') {
                if (Storage::disk('public')->exists('kitchen/' . $image)) {
                    $path = asset('storage/app/public/kitchen/' . $image);
                }
            } else {
                if (Storage::disk('public')->exists('profile/' . $image)) {
                    $path = asset('storage/app/public/profile/' . $image);
                }
            }
        }

        return $path;
    }

    // ===========================
    // STATIC METHODS
    // ===========================
    
    public static function get_chef_branch_name($chef)
    {
        $branch = DB::table('chef_branch')->where('user_id', $chef->id)->get();
        foreach ($branch as $value) {
            $branch_name = Branch::where('id', $value->branch_id)->get();
            foreach ($branch_name as $bn) {
                return $bn->name;
            }
        }
        return null;
    }

    /**
     * Send broadcast notification to all users with FCM tokens
     */
    public static function sendBroadcastNotification(array $data): array
    {
        $results = [
            'topic_sent' => false,
            'in_app_created' => false,
            'users_count' => 0
        ];

        try {
            // Send to FCM topic
            $topicResult = \App\CentralLogics\Helpers::send_push_notif_to_topic(
                [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                    'image' => $data['image'] ?? '',
                    'order_id' => '',
                    'order_status' => ''
                ],
                'notify',
                $data['type'] ?? 'broadcast'
            );
            $results['topic_sent'] = $topicResult !== false;

            // Create broadcast in-app notification
            Notification::broadcast([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'type' => $data['type'] ?? 'broadcast',
                'image' => $data['image'] ?? null,
                'data' => $data['data'] ?? null,
            ]);
            $results['in_app_created'] = true;

            // Count users with FCM tokens
            $results['users_count'] = self::withFcmToken()->count();

            Log::info('Broadcast notification sent', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to send broadcast notification', [
                'error' => $e->getMessage()
            ]);
            return $results;
        }
    }

    /**
     * Generate unique QR code for user (Enhanced with detailed logging)
     */
    public function generateQRCode(): bool
    {
        try {
            Log::info('Starting QR code generation', [
                'user_id' => $this->id,
                'user_name' => $this->name
            ]);

            // Check if GD extension is loaded
            if (!extension_loaded('gd')) {
                Log::error('GD extension not installed', [
                    'user_id' => $this->id,
                    'hint' => 'Install: sudo apt-get install php-gd && sudo systemctl restart apache2'
                ]);
                return false;
            }

            // Check if QrCode class exists
            if (!class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
                Log::error('QrCode package not installed', [
                    'user_id' => $this->id,
                    'hint' => 'Install: composer require simplesoftwareio/simple-qrcode'
                ]);
                return false;
            }

            // Generate unique QR code string
            $qrCode = 'WALLET_' . strtoupper(Str::random(20)) . '_' . $this->id;
            
            // Create filename
            $qrCodeImage = 'qr_' . $this->id . '_' . time() . '.png';
            $directory = storage_path('app/public/qr_codes');
            $fullPath = $directory . '/' . $qrCodeImage;
            
            Log::info('QR code paths', [
                'user_id' => $this->id,
                'directory' => $directory,
                'filename' => $qrCodeImage,
                'full_path' => $fullPath
            ]);

            // Check and create directory
            if (!file_exists($directory)) {
                Log::info('Creating QR codes directory', ['directory' => $directory]);
                
                if (!mkdir($directory, 0775, true)) {
                    Log::error('Failed to create directory', [
                        'user_id' => $this->id,
                        'directory' => $directory,
                        'parent_exists' => file_exists(dirname($directory)),
                        'parent_writable' => is_writable(dirname($directory))
                    ]);
                    return false;
                }
                
                // Set permissions immediately after creation
                chmod($directory, 0775);
                Log::info('Directory created successfully', ['directory' => $directory]);
            }

            // Verify directory is writable
            if (!is_writable($directory)) {
                $perms = substr(sprintf('%o', fileperms($directory)), -4);
                Log::error('Directory not writable', [
                    'user_id' => $this->id,
                    'directory' => $directory,
                    'permissions' => $perms,
                    'owner' => posix_getpwuid(fileowner($directory))['name'] ?? 'unknown',
                    'group' => posix_getgrgid(filegroup($directory))['name'] ?? 'unknown'
                ]);
                return false;
            }

            // Generate QR code data
            $qrData = json_encode([
                'user_id' => $this->id,
                'qr_code' => $qrCode,
                'phone' => $this->phone,
                'name' => $this->name,
                'type' => 'wallet_transfer',
                'generated_at' => now()->toDateTimeString()
            ]);

            Log::info('Generating QR code image', [
                'user_id' => $this->id,
                'data_length' => strlen($qrData)
            ]);

            // Generate QR code using Simple QrCode
            try {
                $qrCodeBinary = QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($qrData);

                // Save to file
                $bytesWritten = file_put_contents($fullPath, $qrCodeBinary);
                
                Log::info('QR code file written', [
                    'user_id' => $this->id,
                    'bytes_written' => $bytesWritten,
                    'path' => $fullPath
                ]);

            } catch (\Exception $qrException) {
                Log::error('QR code generation exception', [
                    'user_id' => $this->id,
                    'error' => $qrException->getMessage(),
                    'trace' => $qrException->getTraceAsString()
                ]);
                return false;
            }

            // Verify file exists and has content
            if (!file_exists($fullPath)) {
                Log::error('QR code file was not created', [
                    'user_id' => $this->id,
                    'path' => $fullPath,
                    'directory_writable' => is_writable($directory)
                ]);
                return false;
            }

            $fileSize = filesize($fullPath);
            if ($fileSize == 0) {
                Log::error('QR code file is empty', [
                    'user_id' => $this->id,
                    'path' => $fullPath
                ]);
                @unlink($fullPath);
                return false;
            }

            // Set file permissions
            chmod($fullPath, 0664);

            Log::info('QR code file created successfully', [
                'user_id' => $this->id,
                'file_size' => $fileSize,
                'path' => $fullPath,
                'permissions' => substr(sprintf('%o', fileperms($fullPath)), -4)
            ]);

            // Delete old QR code image if exists
            if ($this->qr_code_image && $this->qr_code_image !== $qrCodeImage) {
                $oldPath = $directory . '/' . $this->qr_code_image;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                    Log::info('Old QR code deleted', [
                        'user_id' => $this->id,
                        'old_file' => $this->qr_code_image
                    ]);
                }
            }

            // Update user record
            $this->qr_code = $qrCode;
            $this->qr_code_image = $qrCodeImage;
            $saved = $this->save();

            if ($saved) {
                Log::info('QR code generation completed successfully', [
                    'user_id' => $this->id,
                    'qr_code' => $qrCode,
                    'qr_code_image' => $qrCodeImage,
                    'file_size' => $fileSize
                ]);
                return true;
            } else {
                Log::error('Failed to save user record', ['user_id' => $this->id]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('QR code generation failed with exception', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get QR code image URL (Multiple fallback methods)
     */
    public function getQrCodeImageUrlAttribute(): ?string
    {
        if (!$this->qr_code_image) {
            return null;
        }

        $filename = $this->qr_code_image;
        $fullPath = storage_path('app/public/qr_codes/' . $filename);

        // Verify file exists
        if (!file_exists($fullPath)) {
            Log::warning('QR code file not found', [
                'user_id' => $this->id,
                'filename' => $filename,
                'expected_path' => $fullPath
            ]);
            return null;
        }

        // Method 1: Try standard storage URL (preferred)
        if (file_exists(public_path('storage/qr_codes/' . $filename))) {
            return asset('storage/qr_codes/' . $filename);
        }

        // Method 2: Direct storage path (fallback)
        return url('storage/qr_codes/' . $filename);
    }

    /**
     * Get QR code as base64 (works even without storage link)
     */
    public function getQrCodeBase64Attribute(): ?string
    {
        if (!$this->qr_code_image) {
            return null;
        }

        $path = storage_path('app/public/qr_codes/' . $this->qr_code_image);

        if (file_exists($path)) {
            $imageData = file_get_contents($path);
            $base64 = base64_encode($imageData);
            return 'data:image/png;base64,' . $base64;
        }

        return null;
    }

    /**
     * Regenerate QR code
     */
    public function regenerateQRCode(): bool
    {
        return $this->generateQRCode();
    }

    /**
     * Check if user has QR code
     */
    public function hasQRCode(): bool
    {
        return !empty($this->qr_code) && !empty($this->qr_code_image);
    }

    /**
     * Get branch reviews by this user
     */
    public function branchReviews()
    {
        return $this->hasMany(BranchReview::class);
    }

    /**
     * Get service reviews by this user
     */
    public function serviceReviews()
    {
        return $this->hasMany(ServiceReview::class);
    }

    /**
     * Get product reviews by this user
     */
    public function productReviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get delivery man reviews by this user
     */
    public function deliveryManReviews()
    {
        return $this->hasMany(DMReview::class);
    }

    /**
     * Scope to get only customers (users without user_type)
     */
    public function scopeCustomers($query)
    {
        return $query->whereNull('user_type');
    }
}