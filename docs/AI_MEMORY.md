# 🧠 SentinentX AI Memory & Knowledge Base

**Purpose:** Persistent knowledge repository for AI assistants working on SentinentX  
**Last Updated:** 2025-01-20  
**Version:** 1.0  

## 🎯 Project Overview

**SentinentX** is a **sophisticated multi-tenant SaaS cryptocurrency trading bot** that leverages AI consensus for automated trading decisions on Bybit Exchange.

### Core Identity
- **Domain:** AI-powered cryptocurrency trading automation
- **Architecture:** Laravel 12.x + PHP 8.2+ + PostgreSQL + Redis
- **Deployment:** Multi-tenant SaaS with Kubernetes-ready containerization
- **AI Engine:** 3-provider consensus system (OpenAI GPT-4, Google Gemini, Grok)
- **Exchange:** Bybit testnet/mainnet integration
- **Interface:** Telegram bot + Admin API + Customer dashboard

### Business Model
- **SaaS Subscription Plans:** Free (100 AI requests) → Starter ($29) → Pro ($99) → Enterprise ($299)
- **Revenue Streams:** Monthly subscriptions, usage-based billing, white-label partnerships
- **Target Market:** Individual traders, prop trading firms, crypto hedge funds

## 🏗️ System Architecture Deep Dive

### Multi-AI Consensus Flow
```
Market Signal → Technical Analysis → 3-AI Parallel Analysis → Weighted Consensus → Deviation Veto Check → Risk Validation → Position Sizing → Order Execution
```

#### AI Providers & Configuration
- **OpenAI:** GPT-4 model, 0.1 temperature, $0.03/1K tokens
- **Gemini:** Gemini-2.0-flash-exp, 0.1 temperature, free tier
- **Grok:** Grok-2-1212, 0.1 temperature, X.AI API

#### Consensus Algorithm
- **Stage 1:** Independent analysis from each provider
- **Stage 2:** Weighted median calculation with confidence scoring
- **Deviation Veto:** >20% deviation triggers HOLD decision
- **Fallback:** If primary provider fails, automatic failover chain

### Trading System Components

#### Core Services
- **`ConsensusService`** - AI decision aggregation and validation
- **`RiskGuard`** - Multi-layer risk management (daily loss, correlation, leverage)
- **`PositionSizer`** - Dynamic position sizing with ATR-based calculations
- **`TradeManager`** - Order lifecycle management with SL/TP
- **`BybitClient`** - Exchange API integration with rate limiting

#### Risk Management Rules
- **Max Leverage:** 3-125x based on risk profile
- **Daily Loss Limit:** 20% of account equity
- **Position Correlation:** Max 0.85 correlation between open positions
- **Funding Rate Guard:** Auto-close if funding >30 bps
- **ATR-based Stops:** 1.5x ATR for stop loss, 3.0x ATR for take profit

### Multi-Tenant SaaS Architecture

#### Tenant Isolation Strategy
- **Row-Level Security:** All data tables include `tenant_id` foreign key
- **Middleware Scoping:** `TenantContextMiddleware` ensures automatic filtering
- **Plan-Based Limits:** Feature gating and usage enforcement per subscription tier
- **Database Strategy:** Single database with tenant column (not schema-per-tenant)

#### Billing & Usage Tracking
- **Stripe Integration:** Webhook-based subscription management
- **Usage Counters:** Track AI requests, trades, API calls per tenant
- **Plan Enforcement:** Hard limits with graceful degradation for overages
- **Customer Dashboard:** Real-time usage analytics and billing portal

## 🔧 Technical Implementation Details

### Key File Locations & Roles

#### Core Business Logic
- **`app/Services/AI/ConsensusService.php`** - Main AI decision engine
- **`app/Services/Trading/TradeManager.php`** - Position management
- **`app/Services/Risk/RiskGuard.php`** - Risk validation and guards
- **`app/Console/Commands/OpenNowCommand.php`** - Main trading entry point (5-min cron)
- **`app/Console/Commands/ManageOpenCommand.php`** - Position monitoring (5-min cron)

#### Security & Infrastructure
- **`app/Http/Middleware/HmacAuthMiddleware.php`** - API authentication
- **`app/Http/Middleware/TenantContextMiddleware.php`** - Multi-tenant scoping
- **`app/Services/Security/VaultService.php`** - HashiCorp Vault integration
- **`config/security.php`** - Security policies and IP allowlisting

#### Data Models
- **`app/Models/Tenant.php`** - Multi-tenant root entity
- **`app/Models/Trade.php`** - Trading records with tenant isolation
- **`app/Models/Subscription.php`** - Billing and plan management
- **`app/Models/AiLog.php`** - AI decision audit trail

### Configuration Management

#### Environment Variables (Critical)
```bash
# Trading APIs
BYBIT_API_KEY=testnet_key
BYBIT_API_SECRET=testnet_secret
BYBIT_TESTNET=true

# AI Providers
OPENAI_API_KEY=sk-...
GEMINI_API_KEY=AIzaSy...
GROK_API_KEY=grok_...

# Security
HMAC_SECRET=generated_with_openssl_rand
IP_ALLOWLIST="127.0.0.1/32,::1/128"

# Infrastructure
DB_CONNECTION=pgsql
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
```

#### Configuration Files Hierarchy
- **`config/ai.php`** - AI consensus settings, timeouts, deviation thresholds
- **`config/trading.php`** - Risk parameters, leverage limits, symbol configuration
- **`config/exchange.php`** - Bybit API endpoints and rate limits
- **`config/billing.php`** - SaaS plans, limits, and pricing
- **`config/security.php`** - HMAC settings, IP allowlist, rate limiting

## 🚨 Critical Issues & Technical Debt

### Security Concerns (Priority 1)
1. **Hard-coded secrets** in install scripts (`vds_reset_install.sh:338-342`)
2. **Missing .env.example** template for safe development setup
3. **HMAC replay window too large** (5 minutes, should be 60 seconds)
4. **Insufficient input validation** for AI prompts and trading parameters

### Reliability Issues (Priority 1)
1. **No circuit breakers** for external API calls (AI providers, Bybit)
2. **Database queries lack timeouts** - potential for deadlocks
3. **N+1 query problems** in trade history and tenant relationships
4. **Missing graceful degradation** when AI providers fail

### Performance Bottlenecks (Priority 2)
1. **AI consensus latency** averages 2-5 seconds per decision
2. **Market data caching inefficient** with 30-second TTL
3. **No connection pooling** for database and Redis
4. **Synchronous AI calls** block trading operations

### SaaS Readiness Gaps (Priority 2)
1. **Incomplete tenant isolation** - some queries miss tenant scoping
2. **Usage enforcement not comprehensive** - limits checked but not blocked
3. **No self-service onboarding** flow for new customers
4. **Missing customer analytics** and success metrics

## 📋 Operational Procedures

### Development Workflow
1. **Local Setup:** SQLite for development, PostgreSQL for staging/production
2. **Testing Strategy:** 68% current coverage, target >90% for critical paths
3. **CI/CD Pipeline:** GitHub Actions with security scanning, performance testing
4. **Deployment:** Blue-green deployment with feature flags

### Production Operations
- **Monitoring:** Prometheus metrics + Grafana dashboards
- **Logging:** Structured JSON logs with correlation IDs
- **Backup Strategy:** Automated PostgreSQL backups with 30-day retention
- **Health Checks:** `/health` endpoint with dependency validation
- **Scaling:** Kubernetes HPA based on CPU/memory/queue depth

### Telegram Bot Commands
```
/start - Initialize bot and verify API keys
/help - Display all available commands
/status - System health and account information
/balance - Current account balance and equity
/scan - Manual market analysis trigger
/open SYMBOL - Open position with AI analysis
/positions - List all open positions
/close SYMBOL - Close specific position
/pnl - Profit & Loss summary report
```

### Artisan Commands
```bash
# Core trading operations
php artisan sentx:open-now          # Main scanner (*/5 min cron)
php artisan sentx:manage-open       # Position management (*/5 min cron)
php artisan sentx:lab-run           # Backtesting execution

# System management
php artisan sentx:system-check      # Health validation
php artisan sentx:risk-profile      # Configure risk parameters
php artisan telegram:polling        # Bot message handling
```

## 🎯 Strategic Decisions & ADRs

### ADR-001: Multi-AI Consensus Over Single Provider
**Decision:** Use 3-provider consensus instead of single AI provider  
**Rationale:** Reduces single point of failure, improves decision quality, enables comparative analysis  
**Trade-offs:** Higher latency and cost, increased complexity  
**Status:** Implemented

### ADR-002: Row-Level Multi-tenancy Over Schema-per-Tenant
**Decision:** Single database with tenant_id column filtering  
**Rationale:** Simpler maintenance, cost-effective scaling, easier backups  
**Trade-offs:** Requires careful query scoping, shared resource contention  
**Status:** Implemented

### ADR-003: External AI APIs Over Local Models
**Decision:** Use OpenAI, Gemini, Grok APIs instead of hosting models  
**Rationale:** Faster time-to-market, lower infrastructure costs, access to cutting-edge models  
**Trade-offs:** External dependencies, API costs, data privacy considerations  
**Status:** Implemented

### ADR-004: PostgreSQL Over MySQL for Production
**Decision:** PostgreSQL 15+ as primary database  
**Rationale:** Superior JSON support, better performance for complex queries, advanced indexing  
**Trade-offs:** Team familiarity with MySQL, slightly higher resource usage  
**Status:** Implemented

## 🔮 Future Roadmap & Vision

### Q1 2025 (Current): Production Readiness
- Security hardening and vulnerability remediation
- Performance optimization and scalability improvements
- Complete multi-tenant SaaS feature set
- SOC 2 Type II compliance preparation

### Q2 2025: Market Expansion
- Multi-exchange support (Binance, OKX, Coinbase)
- Advanced AI models (custom training, sentiment analysis)
- Mobile applications (iOS, Android)
- European market entry (MiFID II compliance)

### Q3 2025: Enterprise Features
- White-label platform for partners
- Institutional-grade features (prime brokerage, reporting)
- API marketplace for custom strategies
- Advanced analytics and machine learning

### Q4 2025: Global Scale
- Multi-region deployment (US, EU, APAC)
- DeFi integration (DEX trading, yield farming)
- Social trading features (copy trading, leaderboards)
- Regulatory compliance automation

## 🤝 Integration Points & External Dependencies

### Critical External Services
- **Bybit API:** Primary trading execution (testnet/mainnet)
- **OpenAI API:** GPT-4 consensus provider
- **Google AI API:** Gemini consensus provider
- **X.AI API:** Grok consensus provider
- **Stripe API:** Payment processing and billing
- **Telegram Bot API:** User interface and notifications

### Optional External Services
- **CoinGecko API:** Market data supplementation
- **HashiCorp Vault:** Production secret management
- **Slack API:** Team notifications
- **SMTP Providers:** Email notifications

### Internal Service Dependencies
- **PostgreSQL 15+:** Primary data store
- **Redis 7+:** Caching and job queues
- **Laravel Framework:** Application foundation
- **Kubernetes:** Container orchestration (production)

## 💡 Key Learnings & Best Practices

### What Works Well
1. **Multi-AI consensus** significantly improves decision quality vs single provider
2. **Telegram interface** provides excellent user experience for traders
3. **Risk guards** effectively prevent catastrophic losses
4. **Multi-tenant architecture** scales efficiently with proper scoping
5. **Structured logging** enables excellent observability

### What Needs Improvement
1. **Circuit breakers** are essential for external API reliability
2. **Input validation** must be comprehensive for AI prompts
3. **Database query optimization** critical for multi-tenant performance
4. **Usage enforcement** needs real-time blocking, not just tracking
5. **Error handling** must be consistent across all service layers

### Development Tips
- Always mock external APIs in tests (AI providers, exchanges)
- Use database transactions for multi-step trading operations
- Implement idempotency for all trading actions
- Monitor AI provider response times and implement fallbacks
- Never commit real API keys or secrets to version control

---

**⚠️ Important Notes for AI Assistants:**
- This is a **real cryptocurrency trading system** - be extremely careful with any code changes
- Always test on **testnet** before suggesting production changes
- **Security is paramount** - never suggest bypassing authentication or validation
- **Multi-tenancy** must be preserved in all database operations
- When in doubt, prioritize **safety over performance** for trading operations
