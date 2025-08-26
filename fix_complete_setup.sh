#!/bin/bash
set -euo pipefail

echo "🔧 SentinentX Complete Setup Fix"
echo "================================"

# 1. PostgreSQL setup
echo "📊 Setting up PostgreSQL..."
POSTGRES_PASSWORD="sentinentx_secure_$(openssl rand -hex 8)"
sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentinentx;"
sudo -u postgres psql -c "DROP USER IF EXISTS sentinentx;"
sudo -u postgres psql -c "CREATE USER sentinentx WITH PASSWORD '$POSTGRES_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE sentinentx OWNER sentinentx;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentinentx TO sentinentx;"
sudo -u postgres psql -d sentinentx -c "GRANT ALL ON SCHEMA public TO sentinentx;"
echo "✅ PostgreSQL configured with password: $POSTGRES_PASSWORD"

# 2. Redis setup
echo "🔴 Setting up Redis..."
REDIS_PASSWORD="redis_secure_$(openssl rand -hex 8)"
sudo cp /etc/redis/redis.conf /etc/redis/redis.conf.backup
sudo sed -i "s/^# requirepass foobared/requirepass $REDIS_PASSWORD/" /etc/redis/redis.conf
sudo sed -i "s/^requirepass.*/requirepass $REDIS_PASSWORD/" /etc/redis/redis.conf
sudo systemctl restart redis-server
echo "✅ Redis configured with password: $REDIS_PASSWORD"

# 3. Update .env file
echo "📝 Updating .env file..."
cd /var/www/sentinentx
cp env.example.template .env

# Update database settings
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$POSTGRES_PASSWORD/" .env
sed -i "s/REDIS_PASSWORD=.*/REDIS_PASSWORD=$REDIS_PASSWORD/" .env

# Generate other secure values
APP_KEY="base64:$(openssl rand -base64 32)"
HMAC_SECRET="$(openssl rand -hex 32)"

sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" .env
sed -i "s/HMAC_SECRET=.*/HMAC_SECRET=$HMAC_SECRET/" .env

echo "✅ .env file updated"

# 4. Fix systemd service
echo "🔧 Fixing systemd service..."
sudo tee /etc/systemd/system/sentinentx.service > /dev/null << EOF
[Unit]
Description=SentinentX AI Trading Bot - 15 Day Testnet
After=network.target postgresql.service redis-server.service
Wants=postgresql.service redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan trading:start --testnet --duration=15days
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal
SyslogIdentifier=sentinentx

# Security settings
PrivateTmp=true
ProtectSystem=strict
ReadWritePaths=/var/www/sentinentx/storage /var/log
NoNewPrivileges=true

# Resource limits
MemoryMax=1G
CPUQuota=80%

[Install]
WantedBy=multi-user.target
EOF

echo "✅ Systemd service fixed"

# 5. Set proper permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data /var/www/sentinentx
sudo chmod -R 755 /var/www/sentinentx
sudo chmod -R 775 /var/www/sentinentx/storage
sudo chmod -R 775 /var/www/sentinentx/bootstrap/cache

# 6. Laravel setup
echo "🚀 Setting up Laravel..."
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan migrate --force

# 7. Test connections
echo "🧪 Testing connections..."
echo "Testing PostgreSQL..."
sudo -u www-data php artisan tinker --execute="DB::connection()->getPdo(); echo 'PostgreSQL: OK';"

echo "Testing Redis..."
sudo -u www-data php artisan tinker --execute="Redis::ping(); echo 'Redis: OK';"

# 8. Start services
echo "🚀 Starting services..."
sudo systemctl daemon-reload
sudo systemctl enable sentinentx
sudo systemctl restart sentinentx

sleep 5

echo ""
echo "📊 FINAL STATUS:"
echo "================"
echo "PostgreSQL: $(systemctl is-active postgresql)"
echo "Redis: $(systemctl is-active redis-server)"
echo "Nginx: $(systemctl is-active nginx)"
echo "SentinentX: $(systemctl is-active sentinentx)"

echo ""
echo "🔑 CREDENTIALS:"
echo "==============="
echo "PostgreSQL Password: $POSTGRES_PASSWORD"
echo "Redis Password: $REDIS_PASSWORD"
echo "HMAC Secret: $HMAC_SECRET"

echo ""
echo "🎯 SYSTEM READY FOR 15-DAY TESTNET!"
echo "===================================="
echo "Status: systemctl status sentinentx"
echo "Logs: journalctl -u sentinentx -f"
echo "Control: /var/www/sentinentx/control_sentinentx.sh"
