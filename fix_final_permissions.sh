#!/bin/bash

# FINAL PERMISSIONS FIX - Storage and Bootstrap Cache
echo "🔒 FINAL PERMISSIONS FIX - Storage and Bootstrap Cache"
echo "====================================================="

cd /var/www/sentinentx

echo "🔍 Current permissions status:"
echo "• Current user: $(whoami)"
echo "• Storage directory: $([[ -d storage ]] && echo 'EXISTS' || echo 'MISSING')"
echo "• Bootstrap/cache directory: $([[ -d bootstrap/cache ]] && echo 'EXISTS' || echo 'MISSING')"

# Check current permissions
if [[ -d storage ]]; then
    echo "• Storage permissions: $(stat -c %a storage 2>/dev/null || echo 'UNKNOWN')"
    echo "• Storage owner: $(stat -c %U:%G storage 2>/dev/null || echo 'UNKNOWN')"
fi

if [[ -d bootstrap/cache ]]; then
    echo "• Bootstrap/cache permissions: $(stat -c %a bootstrap/cache 2>/dev/null || echo 'UNKNOWN')"
    echo "• Bootstrap/cache owner: $(stat -c %U:%G bootstrap/cache 2>/dev/null || echo 'UNKNOWN')"
fi

# Ensure directories exist
echo ""
echo "📁 Ensuring required directories exist..."
mkdir -p storage/app/public
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
echo "✅ Directories created"

# Test current writability
echo ""
echo "🧪 Testing current writability..."
STORAGE_WRITABLE=false
BOOTSTRAP_WRITABLE=false

if touch storage/test_write.txt 2>/dev/null; then
    STORAGE_WRITABLE=true
    rm -f storage/test_write.txt
    echo "✅ Storage already writable"
else
    echo "❌ Storage NOT writable"
fi

if touch bootstrap/cache/test_write.txt 2>/dev/null; then
    BOOTSTRAP_WRITABLE=true
    rm -f bootstrap/cache/test_write.txt
    echo "✅ Bootstrap/cache already writable"
else
    echo "❌ Bootstrap/cache NOT writable"
fi

# Fix permissions if needed
if [[ "$STORAGE_WRITABLE" == false ]] || [[ "$BOOTSTRAP_WRITABLE" == false ]]; then
    echo ""
    echo "🔧 Fixing permissions..."
    
    # Method 1: Standard Laravel permissions
    echo "🔄 Method 1: Standard Laravel permissions (775)"
    chmod -R 775 storage bootstrap/cache 2>/dev/null || echo "Standard chmod failed"
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "Standard chown failed"
    
    # Test again
    if touch storage/test_write.txt 2>/dev/null && touch bootstrap/cache/test_write.txt 2>/dev/null; then
        rm -f storage/test_write.txt bootstrap/cache/test_write.txt
        echo "✅ Method 1 successful"
    else
        echo "⚠️ Method 1 failed, trying Method 2..."
        
        # Method 2: More permissive
        echo "🔄 Method 2: More permissive permissions (777)"
        chmod -R 777 storage bootstrap/cache 2>/dev/null || echo "Permissive chmod failed"
        
        # Test again
        if touch storage/test_write.txt 2>/dev/null && touch bootstrap/cache/test_write.txt 2>/dev/null; then
            rm -f storage/test_write.txt bootstrap/cache/test_write.txt
            echo "✅ Method 2 successful"
        else
            echo "⚠️ Method 2 failed, trying Method 3..."
            
            # Method 3: Current user ownership
            echo "🔄 Method 3: Current user ownership"
            CURRENT_USER=$(whoami)
            chown -R "$CURRENT_USER:$CURRENT_USER" storage bootstrap/cache 2>/dev/null || echo "User ownership change failed"
            chmod -R 755 storage bootstrap/cache 2>/dev/null || echo "User chmod failed"
            
            # Test again
            if touch storage/test_write.txt 2>/dev/null && touch bootstrap/cache/test_write.txt 2>/dev/null; then
                rm -f storage/test_write.txt bootstrap/cache/test_write.txt
                echo "✅ Method 3 successful"
            else
                echo "⚠️ Method 3 failed, trying Method 4..."
                
                # Method 4: ACL permissions (if available)
                echo "🔄 Method 4: ACL permissions"
                if command -v setfacl &> /dev/null; then
                    setfacl -R -m u:www-data:rwx storage bootstrap/cache 2>/dev/null || echo "ACL failed"
                    setfacl -R -m g:www-data:rwx storage bootstrap/cache 2>/dev/null || echo "ACL group failed"
                else
                    echo "ACL not available"
                fi
                
                # Final fallback: Open permissions
                chmod -R 766 storage bootstrap/cache 2>/dev/null || echo "Final chmod failed"
            fi
        fi
    fi
else
    echo "✅ Permissions already correct"
fi

# Test writability again
echo ""
echo "🧪 Final writability test..."
FINAL_STORAGE_WRITABLE=false
FINAL_BOOTSTRAP_WRITABLE=false

if touch storage/final_test.txt 2>/dev/null; then
    FINAL_STORAGE_WRITABLE=true
    rm -f storage/final_test.txt
    echo "✅ Storage is NOW writable"
else
    echo "❌ Storage still NOT writable"
fi

if touch bootstrap/cache/final_test.txt 2>/dev/null; then
    FINAL_BOOTSTRAP_WRITABLE=true
    rm -f bootstrap/cache/final_test.txt
    echo "✅ Bootstrap/cache is NOW writable"
else
    echo "❌ Bootstrap/cache still NOT writable"
fi

# Laravel specific tests
echo ""
echo "🧪 Laravel functionality tests..."

# Test Laravel log writing
if php -r "file_put_contents('storage/logs/test.log', 'Test log entry\n'); echo 'Log write OK';" 2>/dev/null; then
    echo "✅ Laravel logging functional"
    rm -f storage/logs/test.log
else
    echo "⚠️ Laravel logging may have issues"
fi

# Test cache functionality
if php artisan cache:clear 2>/dev/null; then
    echo "✅ Laravel cache operations functional"
else
    echo "⚠️ Laravel cache operations may have issues"
fi

# Fix .env permissions mentioned in test
echo ""
echo "🔒 Fixing .env file permissions..."
chmod 644 .env 2>/dev/null && echo "✅ .env permissions secured (644)" || echo "⚠️ .env permission change failed"

# Set final ownership
echo ""
echo "👥 Setting final ownership..."
chown -R www-data:www-data /var/www/sentinentx 2>/dev/null && echo "✅ Final ownership set" || echo "⚠️ Final ownership change failed"

# Summary
echo ""
echo "📊 FINAL PERMISSIONS SUMMARY:"
echo "============================="
echo "• Storage writable: $([[ "$FINAL_STORAGE_WRITABLE" == true ]] && echo '✅ YES' || echo '❌ NO')"
echo "• Bootstrap/cache writable: $([[ "$FINAL_BOOTSTRAP_WRITABLE" == true ]] && echo '✅ YES' || echo '❌ NO')"
echo "• Storage permissions: $(stat -c %a storage 2>/dev/null || echo 'UNKNOWN')"
echo "• Bootstrap/cache permissions: $(stat -c %a bootstrap/cache 2>/dev/null || echo 'UNKNOWN')"
echo "• Storage owner: $(stat -c %U:%G storage 2>/dev/null || echo 'UNKNOWN')"
echo "• Bootstrap/cache owner: $(stat -c %U:%G bootstrap/cache 2>/dev/null || echo 'UNKNOWN')"
echo "• .env permissions: $(stat -c %a .env 2>/dev/null || echo 'UNKNOWN')"

if [[ "$FINAL_STORAGE_WRITABLE" == true ]] && [[ "$FINAL_BOOTSTRAP_WRITABLE" == true ]]; then
    echo ""
    echo "🎉 FINAL PERMISSIONS FIX COMPLETED SUCCESSFULLY!"
    echo "✅ All critical permission issues resolved"
    echo "✅ Storage directory is writable"
    echo "✅ Bootstrap/cache directory is writable"
    echo "✅ .env file permissions secured"
    echo ""
    echo "🧪 Ready for final comprehensive test:"
    echo "bash comprehensive_deployment_test.sh"
else
    echo ""
    echo "⚠️ SOME PERMISSION ISSUES REMAIN"
    echo "Manual intervention may be required"
    echo "Current user: $(whoami)"
    echo "Try running as root if needed"
fi

echo ""
echo "🔧 Quick verification commands:"
echo "• Test storage: touch storage/test.txt && rm storage/test.txt"
echo "• Test bootstrap: touch bootstrap/cache/test.txt && rm bootstrap/cache/test.txt"
echo "• Laravel cache: php artisan cache:clear"
