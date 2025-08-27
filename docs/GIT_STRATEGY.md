# SentinentX Git Strategy & Branch Protection

## Branch Strategy

### Main Branches

1. **`main`** - Production-ready code
   - **Protection Level:** MAXIMUM
   - **Direct Push:** ❌ BLOCKED
   - **Merge Requirements:** ALL quality gates must pass
   - **Auto-Deploy:** Production environment
   - **Tagging:** Automatic semantic versioning

2. **`develop`** - Development integration branch
   - **Protection Level:** HIGH
   - **Direct Push:** ❌ BLOCKED (except for hotfixes)
   - **Merge Requirements:** Core quality gates must pass
   - **Auto-Deploy:** Staging environment
   - **Purpose:** Integration testing and staging

### Feature Branches

3. **`feature/*`** - Feature development branches
   - **Naming:** `feature/JIRA-123-short-description`
   - **Source:** Branch from `develop`
   - **Target:** Merge back to `develop`
   - **Lifespan:** Short-lived (1-2 weeks max)
   - **CI:** Basic checks (lint, unit tests)

### Hotfix Branches

4. **`hotfix/*`** - Critical production fixes
   - **Naming:** `hotfix/CRITICAL-123-short-description`
   - **Source:** Branch from `main`
   - **Target:** Merge to both `main` AND `develop`
   - **Priority:** Emergency bypass for critical issues
   - **Approval:** Requires admin approval

## Push Guard Conditions

### Strict Push Conditions (ALL must be GREEN)

#### 🔴 Critical Gates (CI MUST FAIL if any fail)
1. **All Tests Green** - 100% test suite pass rate
2. **Coverage ≥85%** - Code coverage threshold (down from 85% initial to match market standards)
3. **Security Critical=0** - No critical security vulnerabilities
4. **TODO Sweeper=0** - No non-compliant TODO/FIXME/HACK comments
5. **Ubuntu 24.04 Smoke Test PASS** - Deployment compatibility verified

#### 🔒 Environment Integrity Gates
6. **.env Hash Unchanged** - No unauthorized environment modifications
7. **4 Coin Whitelist Active** - ALLOWED_SYMBOLS contains BTC,ETH,SOL,XRP
8. **PGSQL Single Driver** - PostgreSQL-only, no MySQL/SQLite remnants
9. **Telegram-AI RBAC Working** - Role-based access control functional

#### 📊 Quality Gates
10. **Static Analysis Clean** - PHPStan level 8, Pint formatting
11. **Dependency Security** - No vulnerable dependencies
12. **Performance Tests Pass** - No regression in critical paths

## GitHub Branch Protection Rules

### Main Branch Protection
```yaml
# GitHub Settings > Branches > Add Rule
Branch name pattern: main
✅ Restrict pushes that create files larger than 100MB
✅ Require a pull request before merging
  ✅ Require approvals: 2
  ✅ Dismiss stale PR approvals when new commits are pushed
  ✅ Require review from code owners
  ✅ Restrict pushes to users with push access
✅ Require status checks to pass before merging
  ✅ Require branches to be up to date before merging
  Required status checks:
    - preflight
    - todo-sweeper
    - static-analysis
    - test-suite
    - telegram-ai-validation
    - ubuntu-smoke-test
    - quality-gates
✅ Require conversation resolution before merging
✅ Require signed commits
✅ Include administrators
✅ Restrict pushes to users with push access
✅ Allow force pushes: NEVER
✅ Allow deletions: NEVER
```

### Develop Branch Protection
```yaml
Branch name pattern: develop
✅ Require a pull request before merging
  ✅ Require approvals: 1
  ✅ Dismiss stale PR approvals when new commits are pushed
✅ Require status checks to pass before merging
  Required status checks:
    - todo-sweeper
    - static-analysis
    - test-suite
✅ Require conversation resolution before merging
✅ Include administrators
✅ Allow force pushes: Admins only
✅ Allow deletions: Admins only
```

## Workflow Integration

### Pre-Push Hook
- **Location:** `scripts/pre-push-hook.sh`
- **Purpose:** Local validation before push
- **Includes:** TODO sweeper, basic linting
- **Installation:** Automatic via `git config core.hooksPath scripts/`

### CI/CD Workflow
- **File:** `.github/workflows/comprehensive-ci.yml`
- **Triggers:** Push to main/develop, PRs to main/develop
- **Phases:** 10 sequential phases with quality gates
- **Failure Mode:** Fail fast on any critical gate failure

## Semantic Versioning

### Version Format: `vMAJOR.MINOR.PATCH`

- **MAJOR** (v2.0.0): Breaking changes, incompatible API changes
- **MINOR** (v1.1.0): New features, backward compatible
- **PATCH** (v1.0.1): Bug fixes, backward compatible

### Automatic Versioning Rules
```bash
# Based on commit messages:
BREAKING|major    → MAJOR bump
feat|feature      → MINOR bump  
fix|patch|bug     → PATCH bump
docs|style|test   → No version bump
```

### Release Process
1. **Automatic Tagging:** On successful main branch CI
2. **GitHub Release:** Auto-generated with changelog
3. **Release Notes:** Include quality gates status
4. **Docker Tags:** Automatic container builds (if applicable)

## Emergency Procedures

### Hotfix Process
1. **Create:** `git checkout -b hotfix/CRITICAL-123 main`
2. **Fix:** Implement minimal fix
3. **Test:** Emergency test suite (subset)
4. **Approve:** Admin approval required
5. **Merge:** To main AND develop
6. **Deploy:** Immediate production deployment

### Rollback Process
1. **Identify:** Last known good version tag
2. **Revert:** `git revert <commit-range>`
3. **Emergency Deploy:** Skip some quality gates if needed
4. **Document:** Post-mortem required

### Quality Gate Bypass (EMERGENCY ONLY)
- **Authority:** Lead Developer + DevOps approval
- **Conditions:** Production outage, critical security issue
- **Process:** Manual workflow dispatch with override flags
- **Documentation:** Incident report required within 24h

## Developer Workflow

### Feature Development
```bash
# 1. Start feature
git checkout develop
git pull origin develop
git checkout -b feature/SENT-123-new-trading-algo

# 2. Develop with frequent commits
git add .
git commit -m "feat: add new trading algorithm core logic"

# 3. Pre-push validation
./scripts/pre-push-hook.sh

# 4. Push and create PR
git push origin feature/SENT-123-new-trading-algo
# Create PR via GitHub UI: feature → develop

# 5. Address review feedback
git add .
git commit -m "fix: address code review feedback"
git push origin feature/SENT-123-new-trading-algo

# 6. Merge (via GitHub after CI passes)
# 7. Cleanup
git checkout develop
git pull origin develop
git branch -d feature/SENT-123-new-trading-algo
```

### Release Preparation
```bash
# 1. Prepare release branch (if needed for large releases)
git checkout develop
git checkout -b release/v1.2.0

# 2. Final testing and bug fixes
git add .
git commit -m "fix: final pre-release bug fixes"

# 3. Merge to main via PR
# 4. Automatic tagging and deployment
```

## Monitoring & Compliance

### CI/CD Metrics
- **Build Success Rate:** Target >95%
- **Average Build Time:** Target <15 minutes
- **Quality Gate Pass Rate:** Target 100%
- **Security Scan Results:** 0 critical findings

### Branch Health
- **Feature Branch Lifespan:** Target <2 weeks
- **Main Branch Stability:** No direct commits
- **Develop Branch Activity:** Regular integration
- **Hotfix Frequency:** Minimize (<1 per month)

### Compliance Reporting
- **Weekly:** Quality gate pass rates
- **Monthly:** Security scan summaries
- **Quarterly:** Branch strategy effectiveness review
- **On-demand:** Incident response reports

---

**Last Updated:** January 2025  
**Document Owner:** DevOps Team  
**Review Schedule:** Quarterly  
**Approval Required:** Lead Developer, DevOps Lead
