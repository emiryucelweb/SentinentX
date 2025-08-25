# 🚀 SentinentX - AI-Powered Cryptocurrency Trading Bot

**Advanced AI Trading Bot with 2-Stage Consensus System for Bybit Exchange**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue.svg)](https://postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7+-red.svg)](https://redis.io)

## 📋 Overview

SentinentX is a sophisticated cryptocurrency trading bot that leverages multiple AI providers (OpenAI, Gemini, Grok) through a unique 2-stage consensus system to make informed trading decisions on Bybit Exchange.

### 🎯 Key Features

- **🤖 Multi-AI Consensus**: 2-stage decision making with OpenAI, Gemini, and Grok
- **📊 Advanced Risk Management**: Dynamic leverage, position sizing, and deviation veto
- **⚡ Real-time Trading**: Automated position management with SL/TP
- **📱 Telegram Integration**: Complete bot interface for monitoring and control
- **🔬 LAB System**: 15-day backtesting and performance simulation
- **🛡️ Security First**: HMAC authentication, IP allowlisting, and comprehensive logging
- **📈 Performance Monitoring**: Real-time metrics and P&L tracking

## 🏗️ Architecture

### AI Consensus System
```
Stage 1: Independent Analysis
├── OpenAI GPT-4
├── Google Gemini
└── Grok AI

Stage 2: Peer Review
├── Cross-validation with Stage 1 results
├── Weighted median calculation
└── Deviation veto protection
```

### Risk Management
- **Dynamic Leverage**: 3-125x based on risk profile
- **Position Sizing**: Maximum 10% of portfolio per trade
- **ATR-based SL/TP**: Technical analysis driven exits
- **Correlation Service**: Multi-symbol risk assessment

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- PostgreSQL 15+
- Redis 7+
- Composer
- Bybit Testnet Account

### Installation

1. **Clone Repository**
```bash
git clone https://github.com/yourusername/sentinentx.git
cd sentinentx
```

2. **Install Dependencies**
```bash
composer install
```

3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure Environment**
```env
# Bybit Testnet
BYBIT_TESTNET=true
BYBIT_API_KEY=your_testnet_key
BYBIT_API_SECRET=your_testnet_secret

# AI Providers
OPENAI_API_KEY=sk-...
GEMINI_API_KEY=AIzaSy...
GROK_API_KEY=grok_...

# Telegram Bot
TELEGRAM_BOT_TOKEN=7...
TELEGRAM_CHAT_ID=your_chat_id
```

5. **Database Setup**
```bash
php artisan migrate
```

6. **Start Services**
```bash
./start_sentinentx.sh
```

## 📱 Telegram Commands

- `/start` - Initialize bot
- `/help` - Show all commands
- `/status` - System health check
- `/balance` - Account balance
- `/scan` - Market analysis
- `/open SYMBOL` - Open position with AI analysis
- `/positions` - View open positions
- `/close SYMBOL` - Close position
- `/pnl` - Profit & Loss report

## 🔧 Configuration

### Risk Profiles
```bash
php artisan sentx:risk-profile
```
- **Low Risk**: 3-15x leverage
- **Medium Risk**: 15-45x leverage  
- **High Risk**: 45-125x leverage

### LAB System
```bash
# Start 15-day simulation
php artisan sentx:lab-start --days=15 --initial-balance=1000

# Monitor performance
php artisan sentx:lab-monitor
```

## 📊 Monitoring

### System Health
```bash
php artisan sentx:system-check
```

### Performance Metrics
```bash
php artisan sentx:trades --days=7
php artisan sentx:lab-monitor
```

### Logs
```bash
tail -f storage/logs/laravel.log
journalctl -u sentx-queue -f
```

## 🛡️ Security

- **HMAC Authentication**: All API requests signed
- **IP Allowlisting**: Restricted access control
- **Environment Isolation**: Separate testnet/production configs
- **Audit Logging**: Comprehensive activity tracking
- **Rate Limiting**: API abuse protection

## 🏗️ Deployment

### VDS Requirements
- **CPU**: 2 vCPU minimum
- **RAM**: 4GB minimum
- **Storage**: 40GB SSD
- **OS**: Ubuntu 22.04 LTS

### Production Deployment
```bash
# See VDS_DEPLOYMENT_GUIDE.md for complete instructions
./deployment_guide.md
```

## 📁 Project Structure

```
sentinentx/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/Controllers/     # API & Web controllers
│   ├── Models/              # Eloquent models
│   └── Services/            # Business logic
├── config/                  # Configuration files
├── database/               # Migrations & seeders
├── tests/                  # Test suites
├── deployment_guide.md     # Deployment instructions
└── VDS_DEPLOYMENT_GUIDE.md # VDS setup guide
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Coverage report
php artisan test --coverage
```

## 🔄 Development Workflow

1. **Feature Development**
```bash
php artisan make:command NewFeature
php artisan test tests/Feature/NewFeatureTest.php
```

2. **AI Integration**
```bash
php artisan sentx:ai-test
php artisan sentx:consensus-test
```

3. **Trading System**
```bash
php artisan sentx:scan
php artisan sentx:risk-analysis BTCUSDT
```

## 📈 Performance

### Benchmarks (Testnet)
- **AI Response Time**: <3s average
- **Order Execution**: <500ms
- **Consensus Calculation**: <1s
- **System Throughput**: 100+ req/min

### Optimization
- **Redis Caching**: Market data & AI responses
- **Queue Processing**: Async trading operations
- **Database Indexing**: Optimized queries
- **Connection Pooling**: Efficient resource usage

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

### Code Standards
- **PSR-12**: PHP coding standard
- **PHPStan**: Static analysis (Level 8)
- **Laravel Pint**: Code formatting
- **Comprehensive Tests**: 80%+ coverage

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: `/docs` directory
- **Telegram**: @YourSupportBot
- **Issues**: GitHub Issues
- **Email**: support@sentinentx.com

## ⚠️ Disclaimer

**Trading cryptocurrencies involves significant risk and can result in financial loss. This bot is for educational and research purposes. Always test on testnet before using real funds. The developers are not responsible for any financial losses.**

---

**🚀 Built with ❤️ for the crypto trading community**
