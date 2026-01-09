/**
 * Enhanced Admin Notification System - FIXED VERSION
 * Handles real-time notifications for orders and messages
 * Fixed: Only notify once for new items, proper read tracking
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
    
    // State management with localStorage persistence
    const STATE_KEY = 'admin_notification_state';
    
    let state = {
        lastMessageCount: 0,
        lastOrderCount: 0,
        isInitialized: false,
        notifiedMessages: new Set(), // Track which counts we've already notified
        notifiedOrders: new Set(),
        lastCheckTime: 0
    };
    
    // Load state from localStorage
    function loadState() {
        try {
            const saved = localStorage.getItem(STATE_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                state.lastMessageCount = parsed.lastMessageCount || 0;
                state.lastOrderCount = parsed.lastOrderCount || 0;
                state.notifiedMessages = new Set(parsed.notifiedMessages || []);
                state.notifiedOrders = new Set(parsed.notifiedOrders || []);
                state.lastCheckTime = parsed.lastCheckTime || 0;
            }
        } catch (e) {
            console.error('Error loading notification state:', e);
        }
    }
    
    // Save state to localStorage
    function saveState() {
        try {
            localStorage.setItem(STATE_KEY, JSON.stringify({
                lastMessageCount: state.lastMessageCount,
                lastOrderCount: state.lastOrderCount,
                notifiedMessages: Array.from(state.notifiedMessages),
                notifiedOrders: Array.from(state.notifiedOrders),
                lastCheckTime: state.lastCheckTime
            }));
        } catch (e) {
            console.error('Error saving notification state:', e);
        }
    }
    
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
        markedMessagesOnThisPage: false,
        markedOrdersOnThisPage: false,
        
        init() {
            // Mark as read when page loads if on message or order page
            if (this.isMessagePage() && !this.markedMessagesOnThisPage) {
                this.markMessagesAsRead();
                this.markedMessagesOnThisPage = true;
            }
            if (this.isOrderPage() && !this.markedOrdersOnThisPage) {
                this.markOrdersAsRead();
                this.markedOrdersOnThisPage = true;
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
                    state.notifiedMessages.clear();
                    BadgeManager.updateMessageBadge(0);
                    saveState();
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
                    state.notifiedOrders.clear();
                    BadgeManager.updateOrderBadge(0);
                    saveState();
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
            
            // Update state timestamp
            state.lastCheckTime = Date.now();
            
            // First initialization - just set the counts without notification
            if (!state.isInitialized) {
                state.lastMessageCount = newMsgCount;
                state.lastOrderCount = newOrdCount;
                state.isInitialized = true;
                
                // Add current counts to notified set so we don't notify on them
                if (newMsgCount > 0) {
                    state.notifiedMessages.add(newMsgCount);
                }
                if (newOrdCount > 0) {
                    state.notifiedOrders.add(newOrdCount);
                }
                
                saveState();
                BadgeManager.updateMessageBadge(newMsgCount);
                BadgeManager.updateOrderBadge(newOrdCount);
                return;
            }
            
            // Check for NEW messages (count increased AND we haven't notified about this count)
            if (newMsgCount > state.lastMessageCount && !state.notifiedMessages.has(newMsgCount)) {
                const diff = newMsgCount - state.lastMessageCount;
                this.handleNewMessages(diff);
                state.notifiedMessages.add(newMsgCount);
            }
            
            // Check for NEW orders (count increased AND we haven't notified about this count)
            if (newOrdCount > state.lastOrderCount && !state.notifiedOrders.has(newOrdCount)) {
                const diff = newOrdCount - state.lastOrderCount;
                this.handleNewOrders(diff);
                state.notifiedOrders.add(newOrdCount);
            }
            
            // If counts decreased (items were read), update state
            if (newMsgCount < state.lastMessageCount) {
                state.notifiedMessages.clear();
                if (newMsgCount > 0) {
                    state.notifiedMessages.add(newMsgCount);
                }
            }
            
            if (newOrdCount < state.lastOrderCount) {
                state.notifiedOrders.clear();
                if (newOrdCount > 0) {
                    state.notifiedOrders.add(newOrdCount);
                }
            }
            
            // Update state
            state.lastMessageCount = newMsgCount;
            state.lastOrderCount = newOrdCount;
            
            // Save state to localStorage
            saveState();
            
            // Update badges
            BadgeManager.updateMessageBadge(newMsgCount);
            BadgeManager.updateOrderBadge(newOrdCount);
        },
        
        handleNewMessages(count) {
            console.log('🔔 New messages detected:', count);
            
            // Play sound
            AudioManager.play();
            
            // Show desktop notification
            DesktopNotificationManager.show(
                'رسالة جديدة' + (count > 1 ? ' (' + count + ')' : ''),
                `لديك ${count} رسالة جديدة`,
                '/public/assets/admin/img/icons/message.png',
                () => window.location.href = '/admin/message/list'
            );
            
            // Show toast notification
            if (typeof toastr !== 'undefined') {
                toastr.info(
                    `لديك ${count} رسالة جديدة`,
                    'رسالة جديدة',
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
            console.log('🔔 New orders detected:', count);
            
            // Play sound
            AudioManager.play();
            
            // Show desktop notification
            DesktopNotificationManager.show(
                'طلب جديد' + (count > 1 ? ' (' + count + ')' : ''),
                `لديك ${count} طلب جديد`,
                '/public/assets/admin/img/icons/order.png',
                () => window.location.href = '/admin/orders/list/pending'
            );
            
            // Show toast notification
            if (typeof toastr !== 'undefined') {
                toastr.success(
                    `لديك ${count} طلب جديد`,
                    'طلب جديد',
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
                            title="تبديل صوت الإشعارات">
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
                        toastr.success('تم تفعيل صوت الإشعارات');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.info('تم إيقاف صوت الإشعارات');
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
        
        // Load saved state
        loadState();
        
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
            console.log('📊 Initial state:', {
                messages: state.lastMessageCount,
                orders: state.lastOrderCount,
                initialized: state.isInitialized
            });
        }, 2000);
    });
    
    // Export for debugging
    window.NotificationSystem = {
        checkNow: () => NotificationChecker.check(),
        toggleSound: () => $('#soundToggle').click(),
        getState: () => ({
            ...state,
            notifiedMessages: Array.from(state.notifiedMessages),
            notifiedOrders: Array.from(state.notifiedOrders)
        }),
        getConfig: () => CONFIG,
        resetState: () => {
            state = {
                lastMessageCount: 0,
                lastOrderCount: 0,
                isInitialized: false,
                notifiedMessages: new Set(),
                notifiedOrders: new Set(),
                lastCheckTime: 0
            };
            localStorage.removeItem(STATE_KEY);
            console.log('State reset');
        },
        clearNotifications: () => {
            state.notifiedMessages.clear();
            state.notifiedOrders.clear();
            saveState();
            console.log('Notifications cleared');
        }
    };
    
})();