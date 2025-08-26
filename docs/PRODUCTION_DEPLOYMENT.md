# SentientX Production Deployment Guide

## 🚀 Production-Ready Enterprise SaaS Platform

### Prerequisites

#### Infrastructure Requirements
- **Kubernetes Cluster:** v1.28+ (3+ nodes, 16GB RAM each)
- **Database:** MySQL 8.0+ (RDS/CloudSQL recommended) 
- **Cache:** Redis 7.0+ Cluster (ElastiCache/MemoryStore)
- **Storage:** NFS/EFS for shared storage (logs, uploads)
- **Load Balancer:** NGINX Ingress Controller with SSL/TLS
- **Monitoring:** Prometheus + Grafana + AlertManager
- **Secrets:** Vault/AWS Secrets Manager/GCP Secret Manager

#### External Services
- **Exchange APIs:** Bybit API credentials (testnet → mainnet)
- **AI Providers:** OpenAI, Gemini, Grok API keys
- **Notifications:** Slack/Discord/Telegram webhooks
- **Monitoring:** Sentry DSN, New Relic license
- **SSL Certificates:** Let's Encrypt/Commercial CA

---

## 📋 Pre-Deployment Checklist

### ✅ Code Quality Gates
```bash
# All tests must pass
php artisan test
vendor/bin/phpstan analyse --level=max
vendor/bin/php-cs-fixer fix --dry-run
vendor/bin/infection --min-msi=60 --min-covered-msi=60

# Property-based testing
php artisan test --testsuite=Performance
```

### ✅ Security Validation
```bash
# GDPR compliance check
php artisan route:list | grep gdpr
php artisan test --filter=GdprTest

# Security headers check
curl -I https://api.sentinentx.com/health
```

### ✅ Performance Benchmarks
```bash
# Load testing (must pass)
php artisan test --testsuite=Performance
ab -n 1000 -c 10 https://api.sentinentx.com/health

# Database performance
php artisan test --filter=LoadTestingSuite::database_concurrent_operations
```

---

## 🐳 Container Build & Registry

### Multi-Stage Docker Build
```bash
# Build production image
docker build --target production -t sentinentx/app:$(git rev-parse --short HEAD) .
docker build --target production -t sentinentx/app:latest .

# Push to registry
docker push sentinentx/app:$(git rev-parse --short HEAD)
docker push sentinentx/app:latest

# Security scan (recommended)
docker scan sentinentx/app:latest
```

### Image Optimization
- ✅ Multi-stage build (base → production)
- ✅ Non-root user (www:1000)
- ✅ Minimal Alpine Linux base
- ✅ OPcache enabled with preloading
- ✅ Health checks configured
- ✅ Resource limits defined

---

## ☸️ Kubernetes Deployment

### 1. Create Namespace & Secrets
```bash
# Apply namespace
kubectl apply -f k8s/namespace.yaml

# Create secrets (customize secrets.yaml first!)
kubectl apply -f k8s/secrets.yaml

# Verify secrets
kubectl get secrets -n sentinentx
```

### 2. Deploy Infrastructure Components
```bash
# ConfigMaps
kubectl apply -f k8s/configmap.yaml

# Services (before deployments)
kubectl apply -f k8s/service.yaml

# Verify
kubectl get configmap,service -n sentinentx
```

### 3. Deploy Application
```bash
# Main deployment
kubectl apply -f k8s/deployment.yaml

# Auto-scaling
kubectl apply -f k8s/hpa.yaml

# Ingress & networking
kubectl apply -f k8s/ingress.yaml

# Verify deployment
kubectl get pods -n sentinentx -w
kubectl describe deployment sentinentx-app -n sentinentx
```

### 4. Verify Deployment
```bash
# Check pod status
kubectl get pods -n sentinentx
kubectl logs -f deployment/sentinentx-app -n sentinentx

# Test health endpoints
kubectl port-forward svc/sentinentx-app 8080:80 -n sentinentx &
curl http://localhost:8080/health

# Check HPA
kubectl get hpa -n sentinentx
kubectl describe hpa sentinentx-app-hpa -n sentinentx
```

---

## 🔧 Post-Deployment Configuration

### Database Migration & Seeding
```bash
# Run inside app container
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan migrate --force
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan db:seed --class=AiProvidersSeeder

# Verify database
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan sentx:status
```

### Cache & Session Setup
```bash
# Clear and warm cache
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan cache:clear
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan config:cache
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan route:cache
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan view:cache
```

### Queue Workers & Scheduler
```bash
# Verify workers are running
kubectl get pods -l component=worker -n sentinentx
kubectl logs -f deployment/sentinentx-worker -n sentinentx

# Check scheduler
kubectl exec -it deployment/sentinentx-worker -n sentinentx -- php artisan schedule:list
```

---

## 🔍 Health & Monitoring Setup

### Application Health Checks
```bash
# System status
curl https://api.sentinentx.com/api/status

# Health check
curl https://api.sentinentx.com/api/health

# Trading system check
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan sentx:health-check
```

### Monitoring Stack Deployment
```yaml
# Prometheus ServiceMonitor
apiVersion: monitoring.coreos.com/v1
kind: ServiceMonitor
metadata:
  name: sentinentx-metrics
  namespace: sentinentx
spec:
  selector:
    matchLabels:
      app: sentinentx
  endpoints:
  - port: php-fpm
    path: /metrics
    interval: 30s
```

### Log Aggregation
```bash
# Fluentd/Fluent Bit configuration for log shipping
# Logs are structured JSON format for easy parsing
kubectl logs -f deployment/sentinentx-app -n sentinentx | jq .
```

---

## 🔒 Security Hardening

### Network Policies
```bash
# Apply network isolation
kubectl apply -f k8s/ingress.yaml  # Includes NetworkPolicy

# Verify network policies
kubectl get networkpolicy -n sentinentx
kubectl describe networkpolicy sentinentx-network-policy -n sentinentx
```

### SSL/TLS Configuration
```bash
# Verify TLS certificates
kubectl get certificate -n sentinentx
kubectl describe certificate sentinentx-tls -n sentinentx

# Test SSL grade
curl -I https://api.sentinentx.com
```

### RBAC & Service Accounts
```bash
# Create minimal service account
kubectl create serviceaccount sentinentx-sa -n sentinentx
kubectl create rolebinding sentinentx-rb --clusterrole=view --serviceaccount=sentinentx:sentinentx-sa -n sentinentx
```

---

## 📊 Performance Tuning

### PHP-FPM Optimization
```ini
# /usr/local/etc/php-fpm.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 1000
```

### OPcache Configuration
```ini
# Optimized for production workload
opcache.memory_consumption = 256M
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0  # Disable for production
opcache.preload = /var/www/html/config/opcache.php
```

### Database Optimization
```sql
-- MySQL 8.0 optimizations for trading workload
SET GLOBAL innodb_buffer_pool_size = '2G';
SET GLOBAL innodb_log_file_size = '512M';
SET GLOBAL innodb_flush_log_at_trx_commit = 1;
SET GLOBAL max_connections = 500;
```

### Redis Configuration
```conf
# Redis cluster for high availability
maxmemory 2gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

---

## 🚨 Disaster Recovery

### Backup Strategy
```bash
# Database backup (automated via CronJob)
kubectl create cronjob mysql-backup \
  --image=mysql:8.0 \
  --schedule="0 2 * * *" \
  --restart=OnFailure \
  -- /bin/bash -c "mysqldump -h mysql-host -u backup_user -p\$MYSQL_PASSWORD sentinentx_production | gzip > /backup/sentinentx-\$(date +%Y%m%d).sql.gz"

# Application data backup
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan backup:run
```

### Cross-Region Setup
```bash
# Deploy to secondary region (passive standby)
kubectl config use-context prod-region-2
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/secrets.yaml
# ... repeat deployment steps
```

### Recovery Procedures
1. **Database Recovery:** Restore from latest backup
2. **Application Recovery:** Deploy latest known-good image
3. **Cache Recovery:** Redis cluster auto-recovery
4. **DNS Failover:** Update DNS to secondary region (TTL: 300s)

---

## 📈 Scaling Strategy

### Horizontal Pod Autoscaling
```yaml
# Current HPA configuration
- CPU: 70% → scale up
- Memory: 80% → scale up  
- Queue length: 50+ → scale workers
- Min replicas: 3 (app), 2 (workers)
- Max replicas: 10 (app), 8 (workers)
```

### Cluster Autoscaling
```bash
# Node pools for different workloads
gcloud container node-pools create trading-pool \
  --cluster=sentinentx-prod \
  --machine-type=e2-standard-4 \
  --enable-autoscaling \
  --min-nodes=1 \
  --max-nodes=10 \
  --node-taints=trading-workload=true:NoSchedule
```

---

## 🔧 Maintenance Procedures

### Rolling Updates
```bash
# Zero-downtime deployment
kubectl set image deployment/sentinentx-app php-fpm=sentinentx/app:v2.1.0 -n sentinentx
kubectl rollout status deployment/sentinentx-app -n sentinentx

# Rollback if needed
kubectl rollout undo deployment/sentinentx-app -n sentinentx
```

### Database Migrations
```bash
# Run migrations during maintenance window
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan migrate --force

# Verify migration status
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan migrate:status
```

### Cache Clearing
```bash
# Clear application cache
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan cache:clear
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan config:cache
```

---

## 🎯 Success Metrics

### Performance KPIs
- **API Response Time:** p95 < 200ms, p99 < 500ms
- **Database Query Time:** p95 < 50ms  
- **Cache Hit Ratio:** > 95%
- **System Uptime:** > 99.9%
- **Error Rate:** < 0.1%

### Trading Metrics
- **Consensus Time:** < 30s for AI decisions
- **Position Opening:** < 5s end-to-end
- **Risk Guard Response:** < 100ms
- **Queue Processing:** < 2s average

### SaaS Metrics
- **Tenant Isolation:** 100% (no data leaks)
- **GDPR Compliance:** 100% request fulfillment
- **Auto-scaling:** Trigger time < 60s
- **Multi-region Failover:** < 30s RTO

---

## ⚠️ Troubleshooting

### Common Issues
```bash
# Pod not starting
kubectl describe pod <pod-name> -n sentinentx
kubectl logs <pod-name> -n sentinentx --previous

# Database connection issues
kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan tinker
>>> DB::connection()->getPdo();

# Redis connection issues  
kubectl exec -it deployment/sentinentx-app -n sentinentx -- redis-cli -h sentinentx-redis ping

# Storage permissions
kubectl exec -it deployment/sentinentx-app -n sentinentx -- ls -la /var/www/html/storage
```

### Emergency Procedures
1. **Scale down traffic:** `kubectl scale deployment sentinentx-app --replicas=1 -n sentinentx`
2. **Enable maintenance mode:** `kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan down`
3. **Check system health:** `kubectl exec -it deployment/sentinentx-app -n sentinentx -- php artisan sentx:health-check`
4. **Restore from backup:** Follow disaster recovery procedures

---

## 📞 Support Contacts

- **DevOps Team:** devops@sentinentx.com
- **Database Team:** dba@sentinentx.com  
- **Security Team:** security@sentinentx.com
- **On-call Engineering:** +1-XXX-XXX-XXXX
- **Incident Management:** https://status.sentinentx.com

---

## 📝 Change Log

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-01-20 | Initial production deployment |
| 1.1.0 | 2024-02-15 | Added auto-scaling and HPA |
| 1.2.0 | 2024-03-01 | GDPR compliance features |
| 1.3.0 | 2024-03-20 | Multi-region DR setup |

**Last Updated:** 2024-03-20  
**Next Review:** 2024-04-20
