#!/bin/bash

echo "=== Fixing Laravel Storage & QR Code Issues ==="
echo ""

# Get current user
CURRENT_USER=$(whoami)
echo "Current user: $CURRENT_USER"
echo ""

# 1. Fix ownership of problematic directories
echo "Step 1: Fixing file ownership..."
sudo chown -R $CURRENT_USER:$CURRENT_USER storage/app/public/qr_codes
sudo chown -R $CURRENT_USER:$CURRENT_USER storage/app/public
sudo chown -R $CURRENT_USER:$CURRENT_USER storage/framework
sudo chown -R $CURRENT_USER:$CURRENT_USER storage/logs
sudo chown -R $CURRENT_USER:$CURRENT_USER bootstrap/cache
echo "✓ Ownership fixed"
echo ""

# 2. Set proper permissions
echo "Step 2: Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod -R 775 storage/app/public/qr_codes
echo "✓ Permissions set"
echo ""

# 3. Fix web server group
echo "Step 3: Adding web server group..."
sudo chgrp -R www-data storage
sudo chgrp -R www-data bootstrap/cache
echo "✓ Web server group added"
echo ""

# 4. Set ACL permissions for both user and web server
echo "Step 4: Setting ACL permissions..."
sudo setfacl -R -m u:www-data:rwx storage
sudo setfacl -R -d -m u:www-data:rwx storage
sudo setfacl -R -m u:$CURRENT_USER:rwx storage
sudo setfacl -R -d -m u:$CURRENT_USER:rwx storage
echo "✓ ACL permissions set"
echo ""

# 5. Verify storage link
echo "Step 5: Checking storage link..."
if [ -L "public/storage" ]; then
    echo "Storage link exists, removing old link..."
    rm public/storage
fi
php artisan storage:link
echo "✓ Storage link created"
echo ""

# 6. Verify directories exist
echo "Step 6: Creating required directories..."
mkdir -p storage/app/public/qr_codes
mkdir -p storage/app/public/profile
mkdir -p storage/app/public/banner
mkdir -p storage/app/public/branch
mkdir -p storage/app/public/category
mkdir -p storage/app/public/product
mkdir -p storage/app/public/receipts
mkdir -p storage/app/public/whatsapp_template
echo "✓ Directories created"
echo ""

# 7. Clear all caches
echo "Step 7: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Caches cleared"
echo ""

# 8. Test QR code generation
echo "Step 8: Testing QR code generation for user 1..."
php artisan qr:check 1
echo ""

# 9. Display directory structure
echo "Step 9: Verifying structure..."
echo ""
echo "Storage structure:"
ls -la storage/app/public/ | head -15
echo ""
echo "Public storage link:"
ls -la public/storage | head -5
echo ""

# 10. Display permissions
echo "Step 10: Checking permissions..."
echo "QR codes directory:"
ls -la storage/app/public/qr_codes/ | head -10
echo ""

echo "=== Fix Complete! ==="
echo ""
echo "Next steps:"
echo "1. Run: php artisan qr:generate-all --force"
echo "2. Test API: curl http://127.0.0.1:8000/api/v1/customer/wallet/my-qr-code -H 'Authorization: Bearer YOUR_TOKEN'"
echo ""
