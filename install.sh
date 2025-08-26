#!/bin/bash

# SentinentX Installation Script
# Updated: 2025-01-20

set -e

# Color definitions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Logo
print_logo() {
    echo -e "${CYAN}"
    cat << "EOF"
  ███████╗███████╗███╗   ██╗████████╗██╗███╗   ██╗███████╗███╗   ██╗████████╗██╗  ██╗
  ██╔════╝██╔════╝████╗  ██║╚══██╔══╝██║████╗  ██║██╔════╝████╗  ██║╚══██╔══╝╚██╗██╔╝
  ███████╗█████╗  ██╔██╗ ██║   ██║   ██║██╔██╗ ██║█████╗  ██╔██╗ ██║   ██║    ╚███╔╝ 
  ╚════██║██╔══╝  ██║╚██╗██║   ██║   ██║██║╚██╗██║██╔══╝  ██║╚██╗██║   ██║    ██╔██╗ 
  ███████║███████╗██║ ╚████║   ██║   ██║██║ ╚████║███████╗██║ ╚████║   ██║   ██╔╝ ██╗
  ╚══════╝╚══════╝╚═╝  ╚═══╝   ╚═╝   ╚═╝╚═╝  ╚═══╝╚══════╝╚═╝  ╚═══╝   ╚═╝   ╚═╝  ╚═╝
        AI-Powered Cryptocurrency Trading System v2.1.0
EOF
    echo -e "${NC}"
}

# Check system requirements
check_requirements() {
    echo -e "${CYAN}🔍 Checking system requirements...${NC}"
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        echo -e "${RED}❌ PHP is not installed. Please install PHP 8.2 or higher.${NC}"
        exit 1
    fi
    
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -c1-3)
    if [[ $(echo "$PHP_VERSION < 8.2" | bc -l 2>/dev/null || echo "1") -eq 1 ]]; then
        echo -e "${RED}❌ PHP 8.2 or higher is required. Current version: $PHP_VERSION${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ PHP $PHP_VERSION${NC}"
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}❌ Composer is not installed. Please install Composer.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Composer$(composer --version | cut -d' ' -f3)${NC}"
    
    # Check Node.js (optional)
    if command -v node &> /dev/null; then
        echo -e "${GREEN}✅ Node.js $(node --version)${NC}"
    fi
    
    # Check required PHP extensions
    REQUIRED_EXTENSIONS=("curl" "json" "mbstring" "openssl" "pdo" "tokenizer" "xml" "bcmath" "redis")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo -e "${GREEN}✅ PHP $ext extension${NC}"
        else
            echo -e "${RED}❌ PHP $ext extension is missing${NC}"
            exit 1
        fi
    done
}

# Generate secure keys
generate_keys() {
    echo -e "${CYAN}🔑 Generating secure keys...${NC}"
    
    # Generate 32-byte base64 keys
    ENCRYPTION_KEY=$(openssl rand -base64 32)
    HMAC_KEY=$(openssl rand -base64 32)
    BYBIT_HMAC_KEY=$(openssl rand -base64 32)
    
    echo -e "${GREEN}✅ Security keys generated${NC}"
}

# Setup environment
setup_environment() {
    echo -e "${CYAN}⚙️ Setting up environment...${NC}"
    
    # Copy environment template
    if [ ! -f .env ]; then
        if [ -f env.example.template ]; then
            cp env.example.template .env
            echo -e "${GREEN}✅ Environment file created from template${NC}"
        else
            echo -e "${RED}❌ env.example.template not found${NC}"
            exit 1
        fi
    else
        echo -e "${YELLOW}⚠️ .env file already exists, backing up...${NC}"
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    fi
    
    # Replace placeholders with generated keys
    sed -i "s|PLACEHOLDER_TO_BE_REPLACED|${ENCRYPTION_KEY}|g" .env
    sed -i "s|HMAC_SECRET_PLACEHOLDER|${HMAC_KEY}|g" .env
    sed -i "s|BYBIT_HMAC_PLACEHOLDER|${BYBIT_HMAC_KEY}|g" .env
    
    # Generate Laravel app key
    php artisan key:generate --force
    
    echo -e "${GREEN}✅ Environment configured${NC}"
}

# Install dependencies
install_dependencies() {
    echo -e "${CYAN}📦 Installing dependencies...${NC}"
    
    # Install PHP dependencies
    composer install --no-dev --optimize-autoloader
    
    # Install Node.js dependencies (if package.json exists)
    if [ -f package.json ]; then
        if command -v npm &> /dev/null; then
            npm install
        elif command -v yarn &> /dev/null; then
            yarn install
        fi
    fi
    
    echo -e "${GREEN}✅ Dependencies installed${NC}"
}

# Setup database
setup_database() {
    echo -e "${CYAN}🗄️ Setting up database...${NC}"
    
    # Create storage directories
    mkdir -p storage/app/public
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    
    # Set permissions
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    # Run migrations
    php artisan migrate --force
    
    echo -e "${GREEN}✅ Database setup complete${NC}"
}

# Setup optimization
setup_optimization() {
    echo -e "${CYAN}⚡ Optimizing application...${NC}"
    
    # Clear all caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache configurations
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Create storage link
    php artisan storage:link
    
    echo -e "${GREEN}✅ Optimization complete${NC}"
}

# Final checks
run_final_checks() {
    echo -e "${CYAN}🔍 Running final checks...${NC}"
    
    # Test database connection
    if php artisan migrate:status &> /dev/null; then
        echo -e "${GREEN}✅ Database connection working${NC}"
    else
        echo -e "${RED}❌ Database connection failed${NC}"
        exit 1
    fi
    
    # Test basic functionality
    if php artisan tinker --execute="echo 'System check: OK';" | grep -q "OK"; then
        echo -e "${GREEN}✅ Application working${NC}"
    else
        echo -e "${RED}❌ Application check failed${NC}"
        exit 1
    fi
}

# Display completion message
show_completion_message() {
    echo ""
    echo -e "${GREEN}🎉 SentinentX installation completed successfully!${NC}"
    echo ""
    echo -e "${CYAN}📋 Next Steps:${NC}"
    echo -e "${YELLOW}1. Configure your .env file with:${NC}"
    echo "   - Database credentials (DB_*)"
    echo "   - Redis settings (REDIS_*)"
    echo "   - AI provider API keys (OPENAI_API_KEY, etc.)"
    echo "   - Bybit API credentials (BYBIT_*)"
    echo "   - Telegram bot token (TELEGRAM_BOT_TOKEN)"
    echo ""
    echo -e "${YELLOW}2. Start the services:${NC}"
    echo "   ./start.sh"
    echo ""
    echo -e "${YELLOW}3. Check system status:${NC}"
    echo "   ./status.sh"
    echo ""
    echo -e "${YELLOW}4. Run your first LAB test:${NC}"
    echo "   php artisan lab:run"
    echo ""
    echo -e "${YELLOW}5. Start trading:${NC}"
    echo "   php artisan trading:scan"
    echo ""
    echo -e "${CYAN}📚 Documentation:${NC}"
    echo "   - Main config: .env"
    echo "   - AI settings: config/ai.php"
    echo "   - Risk profiles: config/risk_profiles.php"
    echo "   - Trading settings: config/trading.php"
    echo ""
    echo -e "${CYAN}🆘 Support:${NC}"
    echo "   - View logs: tail -f storage/logs/laravel.log"
    echo "   - Run tests: php artisan test"
    echo "   - Health check: curl http://localhost:8000/health"
    echo ""
    echo -e "${GREEN}Happy trading! 🚀💰${NC}"
}

# Main installation process
main() {
    print_logo
    echo -e "${CYAN}Starting SentinentX installation...${NC}"
    echo ""
    
    check_requirements
    generate_keys
    setup_environment
    install_dependencies
    setup_database
    setup_optimization
    run_final_checks
    show_completion_message
}

# Run installation
main