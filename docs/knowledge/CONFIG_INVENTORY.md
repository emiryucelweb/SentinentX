# ⚙️ SentinentX Configuration Inventory

**Generated:** 2025-01-20 | **Environment Analysis:** Development + Production Ready

## 📋 Configuration Summary

**Total Config Files:** 22 files in `/config/`  
**Environment Templates:** 2 (`.env.production.template`, install scripts)  
**Missing:** `.env.example` (must be created)  
**Security Status:** 🟡 Partial (Vault integration ready, but .env fallbacks)

## 🗂️ Core Configuration Files

### 🔧 **Application Core**
| File | Purpose | Critical Settings | Status |
|------|---------|------------------|--------|
| `app.php` | Laravel core | APP_KEY, timezone, locale | ✅ Complete |
| `auth.php` | Authentication | Guards, providers | ✅ Complete |
| `cache.php` | Caching strategy | Redis config | ✅ Complete |
| `database.php` | Database connections | PostgreSQL, SQLite | ✅ Complete |
| `queue.php` | Job processing | Redis queues | ✅ Complete |
| `session.php` | Session management | Redis sessions | ✅ Complete |

### 🤖 **AI & Trading**
| File | Purpose | Critical Settings | Status |
|------|---------|------------------|--------|
| `ai.php` | AI consensus system | Provider configs, timeouts | ✅ Complete |
| `trading.php` | Trading parameters | Leverage, risk limits | ✅ Complete |
| `exchange.php` | Bybit API config | Endpoints, rate limits | ✅ Complete |
| `lab.php` | Backtesting | Simulation parameters | ✅ Complete |

### 🛡️ **Security & Monitoring**
| File | Purpose | Critical Settings | Status |
|------|---------|------------------|--------|
| `security.php` | HMAC, IP allowlist | Secret keys, allowlist | ⚠️ Keys needed |
| `vault.php` | HashiCorp Vault | Vault URL, token | ⚠️ Production setup |
| `health.php` | Health checks | Monitoring thresholds | ✅ Complete |
| `monitoring.php` | Observability | Metrics, logging | ✅ Complete |

### 💰 **SaaS Features**
| File | Purpose | Critical Settings | Status |
|------|---------|------------------|--------|
| `billing.php` | Subscription plans | Plan limits, pricing | ✅ Complete |
| `admin.php` | Admin panel | Admin features | ✅ Complete |
| `database_saas.php` | Multi-tenant DB | Tenant isolation | ✅ Complete |

## 🔑 Environment Variables Inventory

### ❗ **CRITICAL - Must Be Set**

#### 🔐 Security Keys
```bash
# Generate with: php artisan key:generate
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Generate with: openssl rand -hex 32  
HMAC_SECRET=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Bybit API credentials (TESTNET for development)
BYBIT_API_KEY=your_testnet_api_key
BYBIT_API_SECRET=your_testnet_api_secret
BYBIT_TESTNET=true
```

#### 🤖 AI Provider Keys
```bash
# OpenAI GPT-4
OPENAI_API_KEY=sk-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
OPENAI_ENABLED=true

# Google Gemini
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
GEMINI_ENABLED=true

# Grok AI
GROK_API_KEY=grok_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
GROK_ENABLED=true
```

#### 📱 Notification Services
```bash
# Telegram Bot
TELEGRAM_BOT_TOKEN=7XXXXXXXXX:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
TELEGRAM_CHAT_ID=your_chat_id
```

### 🏠 **Infrastructure Settings**

#### 💾 Database
```bash
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentx
DB_USERNAME=sentx  
DB_PASSWORD=strong_password_here
```

#### 🔴 Cache & Queue (Redis)
```bash
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### ⚙️ **Trading Configuration**

#### 📊 Risk Parameters
```bash
TRADING_MAX_LEVERAGE=75
TRADING_MODE_ONE_WAY=true
TRADING_MARGIN_MODE=cross
TRADING_RISK_DAILY_MAX_LOSS_PCT=20
TRADING_RISK_COOLDOWN_MIN=60
TRADING_RISK_MAX_CONCURRENT=4
```

#### 🔬 LAB Settings
```bash
LAB_TEST_MODE=true
LAB_INITIAL_EQUITY=10000
LAB_ACCEPT_MIN_PF=1.2
LAB_ACCEPT_MAX_DD=15
LAB_ACCEPT_MIN_SHARPE=0.8
```

### 🔒 **Security Configuration**

#### 🌐 IP Allowlist
```bash
IP_ALLOWLIST_ENABLED=true
IP_ALLOWLIST="127.0.0.1/32,::1/128"
IP_ALLOWLIST_MODE=deny
```

#### 🚨 Rate Limiting
```bash
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_ATTEMPTS=60
RATE_LIMIT_DECAY_MINUTES=1
```

## 🏭 Production vs Development Differences

### Development Environment
```bash
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite  # For quick setup
BYBIT_TESTNET=true
LOG_LEVEL=debug
```

### Production Environment
```bash
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
BYBIT_TESTNET=false  # ⚠️ Real money!
LOG_LEVEL=info
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

## 🏦 Multi-Tenant Configuration

### Per-Tenant Overrides
Tenants can override these settings via `tenant.settings` JSON column:
- `max_trades_per_day`
- `max_concurrent_positions` 
- `leverage_multiplier`
- `ai_model_preferences`
- `notification_channels`

### Plan-Based Limits (from `billing.php`)
```php
'plans' => [
    'free' => [
        'limits' => [
            'ai_requests' => 100,
            'trades_per_month' => 50,
            'symbols' => 3,
        ]
    ],
    'starter' => [
        'limits' => [
            'ai_requests' => 1000,
            'trades_per_month' => 500,
            'symbols' => 10,
        ]
    ],
    // ... Pro, Enterprise
]
```

## 🔍 Configuration Validation

### Missing/Optional Settings
- [ ] **`.env.example`** - Must be created for easy setup
- [ ] **Market data API keys** (CoinGecko Pro optional)
- [ ] **Slack webhook** (optional notification channel)
- [ ] **Email SMTP** (optional notification channel)

### Environment-Specific Requirements

#### Development Requirements
- SQLite or PostgreSQL
- Redis (optional, file cache fallback)
- Testnet API keys only

#### Production Requirements  
- PostgreSQL 15+ (required)
- Redis 7+ (required)
- HashiCorp Vault (recommended)
- SSL certificates
- Monitoring stack (Prometheus, Grafana)

## 🚨 Security Considerations

### 🔴 High Priority
1. **Never commit real API keys** to version control
2. **Rotate HMAC secrets** regularly in production
3. **Use Vault** for production secret management
4. **Enable IP allowlisting** for admin endpoints
5. **Set strong database passwords**

### 🟡 Medium Priority
1. **Configure SSL/TLS** for all external connections
2. **Set up log rotation** to prevent disk overflow
3. **Monitor rate limits** to prevent API abuse
4. **Configure backup retention** for data protection

### ✅ Current Security Status
- ✅ HMAC authentication implemented
- ✅ IP allowlisting configured
- ✅ Security headers middleware
- ✅ Vault service ready (needs production setup)
- ⚠️ Default secrets in install scripts (placeholder values)

## 📝 Configuration Management Recommendations

### Development Setup
1. **Copy template:** `cp env.production.template .env`
2. **Generate keys:** `php artisan key:generate`
3. **Set testnet APIs:** Update BYBIT_*, OPENAI_*, etc.
4. **Test connection:** `php artisan sentx:system-check`

### Production Deployment
1. **Use Vault:** Migrate all secrets to HashiCorp Vault
2. **Enable monitoring:** Configure Prometheus metrics
3. **Set up backups:** Database + configuration backup
4. **Health checks:** Kubernetes readiness/liveness probes

### Missing `.env.example` Content
```bash
# Should create comprehensive .env.example with:
# - All required variables with placeholder values
# - Comments explaining each section
# - Development vs production differences
# - Security warnings for sensitive values
```

---

**⚠️ Action Required:**
1. Create comprehensive `.env.example` file
2. Document Vault migration path for production
3. Add configuration validation command
4. Set up automated secret rotation procedures
