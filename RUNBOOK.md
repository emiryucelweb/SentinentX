# SentinentX Operations Runbook

🚨 **Emergency Contact**: admin@sentinentx.com  
📞 **Emergency Phone**: [TO_BE_CONFIGURED]  
📊 **Monitoring Dashboard**: [TO_BE_CONFIGURED]  
🔗 **Status Page**: [TO_BE_CONFIGURED]

---

## Table of Contents

1. [Emergency Procedures](#emergency-procedures)
2. [System Components](#system-components)
3. [Monitoring & Alerts](#monitoring--alerts)
4. [Troubleshooting Guides](#troubleshooting-guides)
5. [Maintenance Procedures](#maintenance-procedures)
6. [Escalation Matrix](#escalation-matrix)
7. [Recovery Procedures](#recovery-procedures)

---

## Emergency Procedures

### 🔴 KILL SWITCH - Immediate Trading Stop

**When to use**: Suspected system compromise, runaway trading, critical AI malfunction

```bash
# Immediate trading halt
php artisan sentx:emergency-stop --reason="REASON_HERE"

# Stop all queues and schedulers
sudo systemctl stop sentinentx-worker
sudo systemctl stop sentinentx-scheduler
sudo systemctl stop sentinentx-ws

# Disable webhooks
php artisan sentx:webhook-disable --all

# Close all open positions (if safe to do so)
php artisan sentx:close-all-positions --emergency
```

**Verification**: Check `/status` endpoint returns `maintenance_mode: true`

### 🟠 PARTIAL SHUTDOWN - Component Isolation

**When to use**: Single component failure, suspected component issue

```bash
# Stop specific services
sudo systemctl stop sentinentx-{component}

# Disable specific AI provider
php artisan sentx:ai-provider-disable --provider=openai

# Pause trading for specific symbols
php artisan sentx:pause-symbol --symbol=BTCUSDT

# Enable safe mode (view-only)
php artisan sentx:safe-mode --enable
```

### 🟡 GRACEFUL DEGRADATION

**When to use**: Performance issues, high load, partial service degradation

```bash
# Enable conservative mode
php artisan sentx:mode-conservative

# Reduce AI consensus requirements
php artisan sentx:consensus-adjust --min-providers=2

# Enable circuit breakers
php artisan sentx:circuit-breakers --enable-all

# Scale down non-critical features
php artisan sentx:feature-toggle --non-critical=off
```

---

## System Components

### Core Services

| Service | Binary | Config | Logs | Health Check |
|---------|--------|--------|------|--------------|
| Web Application | `php-fpm` | `/etc/php/8.2/fpm/` | `/var/log/nginx/` | `GET /health` |
| Queue Worker | `sentinentx-worker` | `config/queue.php` | `storage/logs/` | `ps aux \| grep queue` |
| WebSocket | `sentinentx-ws` | `config/websocket.php` | `storage/logs/websocket.log` | `netstat -tlnp \| grep 6001` |
| Scheduler | `sentinentx-scheduler` | `config/scheduler.php` | `storage/logs/scheduler.log` | `ps aux \| grep schedule` |
| Database | `postgresql` | `/etc/postgresql/14/main/` | `/var/log/postgresql/` | `pg_isready` |
| Cache | `redis-server` | `/etc/redis/redis.conf` | `/var/log/redis/` | `redis-cli ping` |

### External Dependencies

| Service | Purpose | Health Check | Fallback |
|---------|---------|--------------|----------|
| Bybit API | Trading execution | `GET /v5/announcements` | Circuit breaker |
| OpenAI API | AI decisions | Custom health endpoint | Disable provider |
| Google Gemini | AI decisions | Custom health endpoint | Disable provider |
| Grok AI | AI decisions | Custom health endpoint | Disable provider |
| Telegram API | Notifications | `getMe` method | Log only |

---

## Monitoring & Alerts

### Alert Levels

| Level | Response Time | Escalation | Examples |
|-------|---------------|------------|----------|
| **CRITICAL** | Immediate (0-5 min) | On-call engineer + Management | Trading halt, Security breach, Data loss |
| **ERROR** | 15 minutes | On-call engineer | API failures, Position management errors |
| **WARNING** | 1 hour | Operations team | Performance degradation, Risk gate failures |
| **INFO** | 24 hours | Logged only | Successful operations, Metrics |

### Key Metrics to Monitor

#### Trading Metrics
- **Open positions count**: Alert if > 50 positions
- **PnL percentage**: Alert if < -10% (daily)
- **Execution latency**: Alert if > 5 seconds
- **Failed trades ratio**: Alert if > 5%

#### System Metrics
- **CPU usage**: Alert if > 80% (sustained 5 min)
- **Memory usage**: Alert if > 85%
- **Disk space**: Alert if > 90%
- **Queue depth**: Alert if > 1000 jobs pending

#### AI Metrics
- **Consensus failures**: Alert if > 10% failure rate
- **AI provider timeouts**: Alert if > 3 timeouts/hour
- **Confidence score**: Alert if avg < 60%

#### Security Metrics
- **Failed authentication**: Alert if > 10 attempts/hour
- **Suspicious API calls**: Alert immediately
- **Risk gate violations**: Alert if critical gates fail

---

## Troubleshooting Guides

### <a name="section-trading-critical"></a>Trading Critical Issues

#### No Trading Activity
1. Check AI providers status: `php artisan sentx:ai-status`
2. Verify exchange connectivity: `php artisan sentx:exchange-ping`
3. Check risk gates: `php artisan sentx:risk-status`
4. Review recent alerts: `tail -f storage/logs/structured.json | grep TRADING`

#### Runaway Trading
1. **IMMEDIATE**: Execute kill switch (see Emergency Procedures)
2. Review last AI decisions: `php artisan sentx:ai-logs --last=10`
3. Check for risk gate bypasses: `grep "risk.*false" storage/logs/risk.json`
4. Analyze position sizes vs configured limits

#### Position Management Failures
1. Check exchange API status
2. Verify position reconciliation: `php artisan sentx:positions-reconcile`
3. Review stop-loss/take-profit configurations
4. Check for network partitions or timeouts

### <a name="section-ai-critical"></a>AI Critical Issues

#### All AI Providers Down
1. Check API keys validity: `php artisan sentx:ai-test-keys`
2. Verify network connectivity to AI providers
3. Check rate limits and quotas
4. Enable emergency consensus mode: `php artisan sentx:consensus-emergency`

#### Consensus Failures
1. Review individual provider responses
2. Check for conflicting decisions
3. Verify market data quality
4. Adjust consensus threshold temporarily

#### Suspicious AI Behavior
1. Review recent decision logs
2. Check for prompt injection attempts
3. Verify input data integrity
4. Enable AI decision audit mode

### <a name="section-exchange-critical"></a>Exchange Critical Issues

#### Exchange API Unavailable
1. Check Bybit status page
2. Verify API credentials
3. Test alternative endpoints
4. Enable trading pause mode

#### WebSocket Disconnections
1. Check network connectivity
2. Verify WebSocket credentials
3. Review connection logs
4. Restart WebSocket service if needed

#### Order Execution Failures
1. Check account balance and permissions
2. Verify symbol configuration
3. Review order size limits
4. Check for maintenance windows

### <a name="section-risk-critical"></a>Risk Critical Issues

#### Risk Gates Bypassed
1. **IMMEDIATE**: Enable emergency stop
2. Review risk configuration changes
3. Check for code changes affecting risk logic
4. Verify user permissions and roles

#### Correlation Spike
1. Review current market conditions
2. Check for unusual correlation patterns
3. Verify correlation calculation logic
4. Consider reducing position sizes

#### Funding Rate Anomalies
1. Check exchange funding rate data
2. Verify funding rate calculations
3. Review funding cost vs position profit
4. Consider position adjustments

### <a name="section-security-critical"></a>Security Critical Issues

#### Unauthorized Access Detected
1. **IMMEDIATE**: Change all API keys and secrets
2. Review access logs for breach scope
3. Check for unauthorized trades or changes
4. Enable enhanced security monitoring

#### API Key Compromise
1. Immediately revoke compromised keys
2. Generate new keys with minimal permissions
3. Review recent API activity
4. Notify relevant stakeholders

#### Suspicious Trading Patterns
1. Review AI decision logs for anomalies
2. Check for external manipulation
3. Verify user activity patterns
4. Enable additional security layers

---

## Maintenance Procedures

### Scheduled Maintenance

#### Database Maintenance
```bash
# Weekly maintenance (Sunday 02:00 UTC)
sudo -u postgres vacuumdb --all --analyze --verbose
sudo -u postgres reindexdb --all --verbose

# Monthly full vacuum (First Sunday 02:00 UTC)
sudo -u postgres vacuumdb --all --full --analyze --verbose
```

#### Log Rotation and Cleanup
```bash
# Daily log rotation
php artisan sentx:logs-rotate

# Weekly cleanup of old logs (>30 days)
find storage/logs/ -name "*.log" -mtime +30 -delete
find storage/logs/ -name "*.json" -mtime +30 -delete
```

#### Cache Management
```bash
# Weekly cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Monthly cache cleanup
redis-cli FLUSHDB
php artisan cache:clear
```

### Emergency Maintenance

#### Database Recovery
```bash
# If database corruption detected
sudo systemctl stop sentinentx-*
sudo -u postgres pg_dump sentinentx > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from latest backup
sudo -u postgres dropdb sentinentx
sudo -u postgres createdb sentinentx
sudo -u postgres psql sentinentx < latest_backup.sql
```

#### Configuration Rollback
```bash
# Rollback to previous configuration
git checkout HEAD~1 config/
php artisan config:cache
sudo systemctl restart sentinentx-*
```

---

## Escalation Matrix

### L1 - Operations Team (Response: 1 hour)
- **Scope**: Routine monitoring, basic troubleshooting
- **Authority**: Service restarts, configuration tuning
- **Escalate to L2 if**: Unable to resolve in 2 hours

### L2 - Engineering Team (Response: 30 minutes)
- **Scope**: Complex issues, code changes, architecture decisions
- **Authority**: Code deployments, database changes
- **Escalate to L3 if**: Critical system failure, security incident

### L3 - Senior Engineering + Management (Response: 15 minutes)
- **Scope**: Business-critical issues, security breaches, data loss
- **Authority**: Emergency procedures, external communications
- **Escalate to L4 if**: Regulatory issues, major financial impact

### L4 - Executive Team (Response: Immediate)
- **Scope**: Company-threatening incidents, regulatory violations
- **Authority**: All decisions, external legal/regulatory response

---

## Recovery Procedures

### Data Recovery

#### Database Point-in-Time Recovery
```bash
# Restore to specific timestamp
sudo -u postgres pg_restore -d sentinentx -t "2024-01-01 12:00:00" backup.dump

# Verify data integrity
php artisan sentx:data-integrity-check
```

#### Configuration Recovery
```bash
# Restore from configuration backup
cp /backup/config/* config/
php artisan config:cache
sudo systemctl restart sentinentx-*
```

### Service Recovery

#### Full System Recovery
```bash
# 1. Stop all services
sudo systemctl stop sentinentx-*

# 2. Restore database
sudo -u postgres psql sentinentx < latest_backup.sql

# 3. Restore configuration
cp /backup/config/* config/
cp /backup/.env .env

# 4. Clear caches
php artisan cache:clear
php artisan config:cache

# 5. Start services in order
sudo systemctl start postgresql
sudo systemctl start redis-server
sudo systemctl start sentinentx-scheduler
sudo systemctl start sentinentx-worker
sudo systemctl start sentinentx-ws
sudo systemctl start nginx

# 6. Verify system health
php artisan sentx:health-check --all
```

#### Partial Service Recovery
```bash
# Restart specific service
sudo systemctl restart sentinentx-{service}

# Verify service health
php artisan sentx:health-check --check={service}

# Monitor logs for errors
tail -f storage/logs/{service}.log
```

---

## Health Check Commands

### Manual Health Checks
```bash
# Complete system health
php artisan sentx:health-check

# Specific component checks
php artisan sentx:health-check --check=telegram
php artisan sentx:health-check --check=exchange
php artisan sentx:health-check --check=database
php artisan sentx:health-check --check=websocket

# Trading system status
php artisan sentx:trading-status

# AI providers status
php artisan sentx:ai-status

# Risk system status
php artisan sentx:risk-status
```

### Automated Monitoring
```bash
# Set up monitoring cron jobs
# Add to crontab -e

# Health check every 5 minutes
*/5 * * * * cd /path/to/sentinentx && php artisan sentx:health-check --quiet >> /var/log/health.log

# Trading status every minute
* * * * * cd /path/to/sentinentx && php artisan sentx:trading-status --quiet >> /var/log/trading.log

# AI status every 10 minutes
*/10 * * * * cd /path/to/sentinentx && php artisan sentx:ai-status --quiet >> /var/log/ai.log
```

---

## Contact Information

### Primary Contacts
- **System Administrator**: admin@sentinentx.com
- **Lead Developer**: dev@sentinentx.com
- **Operations Manager**: ops@sentinentx.com

### External Contacts
- **Bybit Support**: [Bybit API Support]
- **Cloud Provider**: [Cloud Provider Support]
- **Domain Registrar**: [Domain Support]

### Emergency Escalation
1. **Level 1**: Operations Team → ops@sentinentx.com
2. **Level 2**: Engineering Team → dev@sentinentx.com
3. **Level 3**: Management → admin@sentinentx.com
4. **Level 4**: Emergency Contact → [TO_BE_CONFIGURED]

---

## Documentation Updates

**Last Updated**: January 27, 2025  
**Version**: 1.0  
**Next Review**: February 27, 2025  

**Change Log**:
- 2025-01-27: Initial runbook creation
- [Future updates will be logged here]

---

⚠️ **Important**: This runbook should be reviewed and updated monthly. All team members should be familiar with emergency procedures. Keep a printed copy in case of total system failure.
