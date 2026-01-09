/**
 * Enhanced Admin Notification System
 * Handles real-time notifications for orders and messages
 * Fixed: Only notify once for new items, mark as read when visiting pages
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        checkInterval: 10000, // Check every 10 seconds
        soundEnabled: true,
        soundVolume: 0.7,
        showDesktopNotification: true,
        soundFile: '/public/assets/admin/sound/notification.mp3'
    };
    
    // State management
    let state = {
        lastMessageCount: 0,
        lastOrderCount: 0,
        isInitialized: false,
        hasPlayedSound: false,
        lastNotificationTime: {
            message: 0,
            order: 0
        }
    };
    
    // Audio Manager
    const AudioManager = {
        sound: null,
        
        init() {
            this.sound = new Audio(CONFIG.soundFile);
            this.sound.volume = CONFIG.soundVolume;
            this.sound.load();
        },
        
        play() {
            if (!CONFIG.soundEnabled) return;
            
            this.sound.currentTime = 0;
            this.sound.play().catch(() => this.playBeep());
        },
        
        playBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.frequency.value = 800;
                osc.type = 'sine';
                
                gain.gain.setValueAtTime(CONFIG.soundVolume, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.5);
            } catch (e) {
                console.log('Audio not supported');
            }
        }
    };
    
    // Desktop Notification Manager
    const DesktopNotificationManager = {
        permission: 'default',
        
        init() {
            if ('Notification' in window) {
                this.permission = Notification.permission;
                if (this.permission === 'default') {
                    Notification.requestPermission().then(p => this.permission = p);
                }
            }
        },
        
        show(title, body, icon, onClick) {
            if (!CONFIG.showDesktopNotification || this.permission !== 'granted') return;
            
            const notif = new Notification(title, {
                body: body,
                icon: icon || '/public/assets/admin/img/logo.png',
                badge: '/public/assets/admin/img/logo.png',
                tag: 'admin-notif-' + Date.now(),
                requireInteraction: false
            });
            
            if (onClick) {
                notif.onclick = () => {
                    window.focus();
                    onClick();
                    notif.close();
                };
            }
            
            setTimeout(() => notif.close(), 7000);
        }
    };
    
    // Badge Manager
    const BadgeManager = {
        updateBadge(selector, count) {
            const badge = $(selector);
            if (count > 0) {
                badge.text(count).show();
            } else {
                badge.text('0').hide();
            }
        },
        
        updateMessageBadge(count) {
            this.updateBadge('.btn-status-c1:first', count);
        },
        
        updateOrderBadge(count) {
            this.updateBadge('.btn-status-c1:last', count);
        }
    };
    
    // Page Visit Tracker (Mark as Read)
    const PageVisitTracker = {
        init() {
            // Mark as read when page loads if on message or order page
            if (this.isMessagePage()) {
                this.markMessagesAsRead();
            }
            if (this.isOrderPage()) {
                this.markOrdersAsRead();
            }
        },
        
        isMessagePage() {
            const path = window.location.pathname;
            return path.includes('/admin/message') || path.includes('/admin/customer/chat');
        },
        
        isOrderPage() {
            const path = window.location.pathname;
            return path.includes('/admin/orders') || path.includes('/admin/pos/orders');
        },
        
        markMessagesAsRead() {
            $.ajax({
                url: '/admin/mark-messages-read',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: (response) => {
                    console.log('Messages marked as read:', response);
                    state.lastMessageCount = 0;
                    BadgeManager.updateMessageBadge(0);
                },
                error: (error) => {
                    console.error('Error marking messages as read:', error);
                }
            });
        },
        
        markOrdersAsRead() {
            $.ajax({
                url: '/admin/mark-orders-read',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: (response) => {
                    console.log('Orders marked as read:', response);
                    state.lastOrderCount = 0;
                    BadgeManager.updateOrderBadge(0);
                },
                error: (error) => {
                    console.error('Error marking orders as read:', error);
                }
            });
        }
    };
    
    // Notification Checker
    const NotificationChecker = {
        check() {
            $.ajax({
                url: '/admin/get-notification-count',
                type: 'GET',
                success: (res) => this.handleResponse(res),
                error: (err) => console.error('Notification check failed:', err)
            });
        },
        
        handleResponse(res) {
            if (!res.success) return;
            
            const newMsgCount = res.message_count || 0;
            const newOrdCount = res.order_count || 0;
            
            // Only process if initialized (prevent notification on first load)
            if (state.isInitialized) {
                // Check for NEW messages (count increased)
                if (newMsgCount > state.lastMessageCount) {
                    const diff = newMsgCount - state.lastMessageCount;
                    this.handleNewMessages(diff);
                }
                
                // Check for NEW orders (count increased)
                if (newOrdCount > state.lastOrderCount) {
                    const diff = newOrdCount - state.lastOrderCount;
                    this.handleNewOrders(diff);
                }
            }
            
            // Update state
            state.lastMessageCount = newMsgCount;
            state.lastOrderCount = newOrdCount;
            state.isInitialized = true;
            
            // Update badges
            BadgeManager.updateMessageBadge(newMsgCount);
            BadgeManager.updateOrderBadge(newOrdCount);
        },
        
        handleNewMessages(count) {
            // Prevent duplicate notifications within 5 seconds
            const now = Date.now();
            if (now - state.lastNotificationTime.message < 5000) {
                return;
            }
            state.lastNotificationTime.message = now;
            
            // Play sound
            AudioManager.play();
            
            // Show desktop notification
            DesktopNotificationManager.show(
                'New Message' + (count > 1 ? 's' : ''),
                `You have ${count} new message${count > 1 ? 's' : ''}`,
                '/public/assets/admin/img/icons/message.png',
                () => window.location.href = '/admin/message/list'
            );
            
            // Show toast notification
            if (typeof toastr !== 'undefined') {
                toastr.info(
                    `You have ${count} new message${count > 1 ? 's' : ''}`,
                    'New Message' + (count > 1 ? 's' : ''),
                    {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000,
                        onclick: () => window.location.href = '/admin/message/list'
                    }
                );
            }
        },
        
        handleNewOrders(count) {
            // Prevent duplicate notifications within 5 seconds
            const now = Date.now();
            if (now - state.lastNotificationTime.order < 5000) {
                return;
            }
            state.lastNotificationTime.order = now;
            
            // Play sound
            AudioManager.play();
            
            // Show desktop notification
            DesktopNotificationManager.show(
                'New Order' + (count > 1 ? 's' : ''),
                `You have ${count} new order${count > 1 ? 's' : ''}`,
                '/public/assets/admin/img/icons/order.png',
                () => window.location.href = '/admin/orders/list/pending'
            );
            
            // Show toast notification
            if (typeof toastr !== 'undefined') {
                toastr.success(
                    `You have ${count} new order${count > 1 ? 's' : ''}`,
                    'New Order' + (count > 1 ? 's' : ''),
                    {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000,
                        onclick: () => window.location.href = '/admin/orders/list/pending'
                    }
                );
            }
            
            // Show modal (optional)
            if (typeof $('#popup-modal').modal === 'function') {
                $('#popup-modal').modal('show');
            }
        }
    };
    
    // Sound Toggle UI
    const SoundToggleUI = {
        init() {
            this.addButton();
            this.loadPreferences();
        },
        
        addButton() {
            const btn = `
                <li class="nav-item d-none d-sm-inline-block">
                    <button id="soundToggle" class="btn btn-icon btn-ghost-secondary rounded-circle" 
                            title="Toggle notification sound">
                        <i class="tio-notifications-on-outlined"></i>
                    </button>
                </li>
            `;
            
            const target = $('.navbar-nav.align-items-center.flex-row li').first();
            if (target.length) {
                target.before(btn);
                this.attachHandler();
            }
        },
        
        attachHandler() {
            $('#soundToggle').on('click', () => {
                CONFIG.soundEnabled = !CONFIG.soundEnabled;
                this.updateButton();
                this.savePreferences();
                
                if (CONFIG.soundEnabled) {
                    AudioManager.play();
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Notification sound enabled');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.info('Notification sound disabled');
                    }
                }
            });
        },
        
        updateButton() {
            const btn = $('#soundToggle');
            const icon = btn.find('i');
            
            if (CONFIG.soundEnabled) {
                icon.removeClass('tio-notifications-off-outlined').addClass('tio-notifications-on-outlined');
                btn.removeClass('btn-ghost-danger').addClass('btn-ghost-secondary');
            } else {
                icon.removeClass('tio-notifications-on-outlined').addClass('tio-notifications-off-outlined');
                btn.removeClass('btn-ghost-secondary').addClass('btn-ghost-danger');
            }
        },
        
        savePreferences() {
            localStorage.setItem('notificationSoundEnabled', CONFIG.soundEnabled);
        },
        
        loadPreferences() {
            const saved = localStorage.getItem('notificationSoundEnabled');
            if (saved !== null) {
                CONFIG.soundEnabled = saved === 'true';
                this.updateButton();
            }
        }
    };
    
    // Initialize
    $(document).ready(function() {
        console.log('🔔 Initializing notification system...');
        
        // Initialize components
        AudioManager.init();
        DesktopNotificationManager.init();
        SoundToggleUI.init();
        
        // Mark messages/orders as read if on those pages
        PageVisitTracker.init();
        
        // Wait 2 seconds before first check to allow page to fully load
        setTimeout(() => {
            NotificationChecker.check();
            
            // Then check every CONFIG.checkInterval
            setInterval(() => NotificationChecker.check(), CONFIG.checkInterval);
            
            console.log('✅ Notification system initialized');
        }, 2000);
    });
    
    // Export for debugging
    window.NotificationSystem = {
        checkNow: () => NotificationChecker.check(),
        toggleSound: () => $('#soundToggle').click(),
        getState: () => state,
        getConfig: () => CONFIG,
        resetState: () => {
            state.isInitialized = false;
            state.lastMessageCount = 0;
            state.lastOrderCount = 0;
            console.log('State reset');
        }
    };
    
})();