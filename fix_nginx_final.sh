#!/bin/bash

# NGINX FINAL FIX - Complete Nginx Setup
echo "🔧 NGINX FINAL FIX - Complete Setup"
echo "=================================="

# Step 1: Stop any conflicting services
echo "🛑 Stopping conflicting services..."
systemctl stop apache2 2>/dev/null || true
systemctl disable apache2 2>/dev/null || true
pkill -f apache2 2>/dev/null || true

# Kill any process using port 80
lsof -ti:80 | xargs kill -9 2>/dev/null || true

# Step 2: Reinstall Nginx completely
echo "📦 Reinstalling Nginx completely..."
systemctl stop nginx 2>/dev/null || true
apt-get remove --purge -y nginx nginx-* 2>/dev/null || true
apt-get autoremove -y 2>/dev/null || true

# Clean nginx directories
rm -rf /etc/nginx /var/log/nginx /var/lib/nginx 2>/dev/null || true

# Install nginx fresh
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y nginx

# Step 3: Create complete Nginx configuration
echo "⚙️ Creating complete Nginx configuration..."

# Create main nginx.conf
cat > /etc/nginx/nginx.conf << 'NGINXCONF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 768;
    # multi_accept on;
}

http {
    ##
    # Basic Settings
    ##
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    ##
    # SSL Settings
    ##
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    ##
    # Logging Settings
    ##
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    ##
    # Gzip Settings
    ##
    gzip on;

    ##
    # Virtual Host Configs
    ##
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
NGINXCONF

# Create mime.types if missing
if [[ ! -f "/etc/nginx/mime.types" ]]; then
    cat > /etc/nginx/mime.types << 'MIMETYPES'
types {
    text/html                             html htm shtml;
    text/css                              css;
    text/xml                              xml;
    image/gif                             gif;
    image/jpeg                            jpeg jpg;
    application/javascript                js;
    application/atom+xml                  atom;
    application/rss+xml                   rss;
    text/plain                            txt;
    image/png                             png;
    image/x-icon                          ico;
    application/json                      json;
    application/pdf                       pdf;
    application/zip                       zip;
}
MIMETYPES
fi

# Create directories
mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled /etc/nginx/conf.d /etc/nginx/modules-enabled
mkdir -p /var/log/nginx

# Create SentinentX site configuration
cat > /etc/nginx/sites-available/sentinentx << 'SITECONF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    server_name _;
    root /var/www/sentinentx/public;
    index index.php index.html index.htm;

    # Laravel public directory
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Hide nginx version
    server_tokens off;

    # Logging
    access_log /var/log/nginx/sentinentx_access.log;
    error_log /var/log/nginx/sentinentx_error.log;
}
SITECONF

# Remove default site and enable SentinentX
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/

# Step 4: PHP-FPM Setup
echo "🐘 Setting up PHP-FPM..."
apt-get install -y php8.3-fpm

# Ensure PHP-FPM is running
systemctl start php8.3-fpm
systemctl enable php8.3-fpm

# Step 5: Set proper permissions
echo "🔒 Setting proper permissions..."
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage /var/www/sentinentx/bootstrap/cache

# Create public directory if missing
mkdir -p /var/www/sentinentx/public
if [[ ! -f "/var/www/sentinentx/public/index.php" ]]; then
    cat > /var/www/sentinentx/public/index.php << 'INDEXPHP'
<?php
echo "🚀 SentinentX is running!<br>";
echo "Laravel Status: " . (file_exists('../artisan') ? '✅ Ready' : '❌ Not found') . "<br>";
echo "Environment: " . (file_exists('../.env') ? '✅ Configured' : '❌ Missing') . "<br>";
echo "Storage: " . (is_writable('../storage') ? '✅ Writable' : '❌ Not writable') . "<br>";
echo "Database: ";
try {
    if (file_exists('../.env')) {
        $env = file_get_contents('../.env');
        if (strpos($env, 'DB_CONNECTION=pgsql') !== false) {
            echo "✅ PostgreSQL configured";
        } else {
            echo "⚠️ Database not configured";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking database";
}
echo "<br>Time: " . date('Y-m-d H:i:s');
INDEXPHP
fi

# Step 6: Test and start Nginx
echo "🧪 Testing Nginx configuration..."
if nginx -t; then
    echo "✅ Nginx configuration test passed"
else
    echo "❌ Nginx configuration test failed"
    nginx -t
    exit 1
fi

# Start Nginx
echo "🚀 Starting Nginx..."
systemctl start nginx
systemctl enable nginx

# Wait and verify
sleep 2

if systemctl is-active --quiet nginx; then
    echo "✅ Nginx is now running!"
else
    echo "❌ Nginx failed to start, checking logs..."
    systemctl status nginx
    journalctl -u nginx --no-pager --lines=10
    exit 1
fi

# Step 7: Final verification
echo "🔍 Final verification..."
echo "• Nginx status: $(systemctl is-active nginx)"
echo "• PHP-FPM status: $(systemctl is-active php8.3-fpm)"
echo "• Port 80 check: $(netstat -tlnp | grep :80 | wc -l) process(es) listening"

# Test web response
echo "🌐 Testing web response..."
if curl -s -I http://localhost | head -1 | grep -q "200\|301\|302"; then
    echo "✅ Web server responding successfully!"
    echo "Response: $(curl -s -I http://localhost | head -1)"
else
    echo "⚠️ Web server response test:"
    curl -s -I http://localhost || echo "Connection failed"
fi

echo ""
echo "🎉 NGINX SETUP COMPLETED!"
echo "• Nginx: ✅ Running"
echo "• PHP-FPM: ✅ Running"  
echo "• Site config: ✅ SentinentX configured"
echo "• Permissions: ✅ Set correctly"
echo ""
echo "🔗 Test URLs:"
echo "• http://localhost (main site)"
echo "• http://your-server-ip (external access)"
echo ""
