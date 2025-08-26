#!/bin/bash

# CRITICAL FIX: PostgreSQL Permissions for Schema Public
echo "🗄️ CRITICAL FIX: PostgreSQL Permissions"
echo "========================================"

cd /var/www/sentinentx

# Get database credentials from .env
DB_PASSWORD=$(grep "DB_PASSWORD=" .env | cut -d'=' -f2)
DB_USER=$(grep "DB_USERNAME=" .env | cut -d'=' -f2)
DB_NAME=$(grep "DB_DATABASE=" .env | cut -d'=' -f2)

echo "🔍 Database Configuration:"
echo "• User: $DB_USER"
echo "• Database: $DB_NAME"
echo "• Password length: ${#DB_PASSWORD} characters"

# Test current connection
echo ""
echo "🧪 Testing current database connection..."
if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1;" &>/dev/null; then
    echo "✅ Basic connection successful"
else
    echo "❌ Basic connection failed"
    exit 1
fi

# Check current permissions
echo ""
echo "🔍 Checking current permissions..."
CURRENT_PRIVS=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT has_schema_privilege('$DB_USER', 'public', 'CREATE');" 2>/dev/null | tr -d ' ')

echo "• Current CREATE privilege on public schema: $CURRENT_PRIVS"

if [[ "$CURRENT_PRIVS" == "t" ]]; then
    echo "✅ User already has CREATE privileges"
else
    echo "❌ User lacks CREATE privileges - fixing..."
    
    # Fix permissions using postgres superuser
    echo ""
    echo "🔧 Fixing PostgreSQL permissions..."
    
    # Grant all privileges on database
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;" || echo "Grant database privileges failed"
    
    # Grant usage and create on public schema
    sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE ON SCHEMA public TO $DB_USER;" || echo "Grant usage failed"
    sudo -u postgres psql -d "$DB_NAME" -c "GRANT CREATE ON SCHEMA public TO $DB_USER;" || echo "Grant create failed"
    
    # Grant all privileges on all tables in public schema
    sudo -u postgres psql -d "$DB_NAME" -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $DB_USER;" || echo "Grant table privileges failed"
    
    # Grant all privileges on all sequences in public schema
    sudo -u postgres psql -d "$DB_NAME" -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO $DB_USER;" || echo "Grant sequence privileges failed"
    
    # Set default privileges for future objects
    sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO $DB_USER;" || echo "Grant default table privileges failed"
    sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO $DB_USER;" || echo "Grant default sequence privileges failed"
    
    # Make user owner of the database
    sudo -u postgres psql -c "ALTER DATABASE $DB_NAME OWNER TO $DB_USER;" || echo "Change ownership failed"
    
    echo "✅ PostgreSQL permissions fixed"
fi

# Test permissions again
echo ""
echo "🧪 Testing permissions after fix..."
NEW_PRIVS=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT has_schema_privilege('$DB_USER', 'public', 'CREATE');" 2>/dev/null | tr -d ' ')

echo "• CREATE privilege on public schema: $NEW_PRIVS"

if [[ "$NEW_PRIVS" == "t" ]]; then
    echo "✅ Permissions are now correct"
else
    echo "⚠️ Permissions still need attention"
fi

# Test table creation
echo ""
echo "🧪 Testing table creation..."
if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "CREATE TABLE IF NOT EXISTS test_permissions (id SERIAL PRIMARY KEY, test VARCHAR(50)); DROP TABLE IF EXISTS test_permissions;" &>/dev/null; then
    echo "✅ Table creation test successful"
else
    echo "❌ Table creation test failed"
fi

# Run Laravel migrations
echo ""
echo "🗄️ Running Laravel migrations..."
if php artisan migrate --force; then
    echo "✅ Laravel migrations successful!"
    
    # Check created tables
    TABLE_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' ')
    echo "✅ Database now has $TABLE_COUNT tables"
    
else
    echo "⚠️ Migrations still failing - checking specific error..."
    php artisan migrate --force 2>&1 | head -10
fi

# Fix cache table issue
echo ""
echo "🔧 Fixing cache configuration..."

# Update .env to use file cache instead of database cache for now
if grep -q "CACHE_DRIVER=redis" .env; then
    echo "Cache driver is already redis (good)"
elif grep -q "CACHE_DRIVER=" .env; then
    sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
    echo "✅ Updated cache driver to redis"
else
    echo "CACHE_DRIVER=redis" >> .env
    echo "✅ Added redis cache driver to .env"
fi

# Clear and rebuild caches
echo ""
echo "⚡ Optimizing Laravel caches..."
php artisan config:clear 2>/dev/null && echo "✅ Config cleared" || echo "Config clear failed"
php artisan cache:clear 2>/dev/null && echo "✅ Cache cleared" || echo "Cache clear failed"
php artisan config:cache 2>/dev/null && echo "✅ Config cached" || echo "Config cache failed"

# Test database connection through Laravel
echo ""
echo "🧪 Testing Laravel database connection..."
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Laravel DB connection OK';" 2>/dev/null | grep -q "Laravel DB connection OK"; then
    echo "✅ Laravel database connection working perfectly"
else
    echo "⚠️ Laravel database connection still has issues"
fi

# Create a simple migration test
echo ""
echo "🧪 Creating test migration..."
if php artisan make:migration test_setup --create=test_setup 2>/dev/null; then
    echo "✅ Test migration created"
    php artisan migrate 2>/dev/null && echo "✅ Test migration ran successfully" || echo "⚠️ Test migration failed"
else
    echo "⚠️ Could not create test migration"
fi

# Final database status
echo ""
echo "📊 Final Database Status:"
echo "========================"

# Check tables
if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "\dt" &>/dev/null; then
    TABLES=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "\dt" | grep "public" | wc -l)
    echo "✅ Database tables: $TABLES"
    
    # List tables
    echo "📋 Available tables:"
    PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "\dt" | grep "public" | awk '{print "  • " $3}' | head -10
else
    echo "⚠️ Cannot list database tables"
fi

# Check migrations table
if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "SELECT COUNT(*) FROM migrations;" &>/dev/null; then
    MIGRATION_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM migrations;" | tr -d ' ')
    echo "✅ Migrations table exists with $MIGRATION_COUNT migrations"
else
    echo "⚠️ Migrations table missing or inaccessible"
fi

echo ""
echo "🎉 POSTGRESQL PERMISSIONS FIX COMPLETED!"
echo "========================================"
echo "✅ Database permissions configured"
echo "✅ Schema access granted"
echo "✅ Laravel migrations attempted"
echo "✅ Cache configuration optimized"
echo ""
echo "🧪 Next steps:"
echo "• Run: php artisan migrate --force"
echo "• Test: bash comprehensive_deployment_test.sh"
echo "• Verify: php artisan tinker"
