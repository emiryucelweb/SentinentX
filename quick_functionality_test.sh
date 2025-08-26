#!/bin/bash

# QUICK FUNCTIONALITY TEST - Essential Functions Only
echo "⚡ QUICK SENTINENTX FUNCTIONALITY TEST ⚡"
echo "======================================="

cd /var/www/sentinentx

# Quick system check
echo "🔍 Quick System Check:"
echo "• PostgreSQL: $(systemctl is-active postgresql)"
echo "• Nginx: $(systemctl is-active nginx)" 
echo "• Redis: $(systemctl is-active redis-server)"
echo "• HTTP Response: $(curl -s -I http://localhost | head -1 | cut -d' ' -f2 || echo 'FAILED')"

echo ""
echo "📁 Laravel Structure:"
echo "• .env file: $([[ -f .env ]] && echo '✅' || echo '❌') ($(wc -l < .env 2>/dev/null || echo 0) lines)"
echo "• App key: $([[ -f .env ]] && grep -q 'APP_KEY=base64:' .env && echo '✅' || echo '❌')"
echo "• Storage writable: $([[ -w storage ]] && echo '✅' || echo '❌')"

echo ""
echo "🗄️ Database Test:"
DB_PASSWORD=$(grep "DB_PASSWORD=" .env | cut -d'=' -f2)
DB_USER=$(grep "DB_USERNAME=" .env | cut -d'=' -f2) 
DB_NAME=$(grep "DB_DATABASE=" .env | cut -d'=' -f2)

if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1;" &>/dev/null; then
    echo "• Database connection: ✅"
else
    echo "• Database connection: ❌"
fi

echo ""
echo "🔴 Redis Test:"
if redis-cli ping 2>/dev/null | grep -q "PONG"; then
    echo "• Redis connection: ✅"
else
    echo "• Redis connection: ❌"
fi

echo ""
echo "🤖 AI Configuration:"
AI_COUNT=0
for ai in OPENAI GEMINI GROK; do
    if grep -q "${ai}_API_KEY=" .env && ! grep -q "${ai}_API_KEY=$" .env; then
        echo "• $ai: ✅ Configured"
        ((AI_COUNT++))
    else
        echo "• $ai: ⚠️ Not configured"
    fi
done

echo ""
echo "📊 Quick Summary:"
ESSENTIAL_CHECKS=0
systemctl is-active --quiet postgresql && ((ESSENTIAL_CHECKS++))
systemctl is-active --quiet nginx && ((ESSENTIAL_CHECKS++))
systemctl is-active --quiet redis-server && ((ESSENTIAL_CHECKS++))
[[ -f .env ]] && grep -q 'APP_KEY=base64:' .env && ((ESSENTIAL_CHECKS++))

echo "• Essential services: $ESSENTIAL_CHECKS/4 ✅"
echo "• AI providers: $AI_COUNT/3 ✅"

if [[ $ESSENTIAL_CHECKS -eq 4 ]]; then
    echo -e "\n🎉 QUICK TEST: ALL ESSENTIAL FUNCTIONS WORKING! 🎉"
    echo "Ready for comprehensive testing or production use!"
else
    echo -e "\n⚠️ QUICK TEST: $((4-ESSENTIAL_CHECKS)) essential functions need attention"
fi

echo ""
echo "💡 Run comprehensive test with:"
echo "bash comprehensive_deployment_test.sh"
