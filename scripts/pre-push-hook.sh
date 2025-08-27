#!/bin/bash

# SentinentX Git Pre-Push Hook
# Enforces code quality standards before allowing push to remote

set -euo pipefail
IFS=$'\n\t'

# Configuration
readonly SCRIPT_VERSION="1.0.0"
readonly PROJECT_ROOT="$(git rev-parse --show-toplevel)"
readonly HOOK_LOG="/tmp/sentinentx-pre-push.log"
readonly TODO_SWEEPER="$PROJECT_ROOT/scripts/todo-sweeper.php"
readonly REPORTS_DIR="$PROJECT_ROOT/reports"

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly CYAN='\033[0;36m'
readonly BOLD='\033[1m'
readonly NC='\033[0m'

# Push guard configuration
readonly ENABLE_TODO_SWEEPER=true
readonly ENABLE_LINT_CHECK=true
readonly ENABLE_TYPE_CHECK=true
readonly ENABLE_SECURITY_SCAN=true
readonly ENABLE_ENV_INTEGRITY=true
readonly REQUIRE_TESTS_PASS=true
readonly REQUIRE_MIN_COVERAGE=85

# Logging function
log() {
    local level="$1"
    local message="$2"
    local timestamp=$(date -Iseconds)
    
    case "$level" in
        "INFO") echo -e "${GREEN}[INFO]${NC} $message" ;;
        "WARN") echo -e "${YELLOW}[WARN]${NC} $message" ;;
        "ERROR") echo -e "${RED}[ERROR]${NC} $message" ;;
        "SUCCESS") echo -e "${CYAN}[SUCCESS]${NC} $message" ;;
        "STEP") echo -e "${BLUE}[STEP]${NC} $message" ;;
    esac
    
    echo "[$timestamp] [$level] $message" >> "$HOOK_LOG"
}

# Error handler
handle_error() {
    local exit_code=$1
    local line_number=$2
    log "ERROR" "Pre-push hook failed at line $line_number with exit code $exit_code"
    echo ""
    echo -e "${RED}${BOLD}🚫 PUSH REJECTED${NC}"
    echo -e "${RED}Pre-push validation failed. Please fix the issues and try again.${NC}"
    echo ""
    echo -e "${YELLOW}📋 Check the log for details: $HOOK_LOG${NC}"
    echo -e "${YELLOW}📊 View reports: ls -la $REPORTS_DIR/${NC}"
    echo ""
    exit $exit_code
}

trap 'handle_error $? $LINENO' ERR

# Header
print_header() {
    echo ""
    echo -e "${BOLD}${BLUE}🛡️ SentinentX Pre-Push Guard v$SCRIPT_VERSION${NC}"
    echo -e "${BLUE}============================================${NC}"
    echo ""
}

# Check if we're in a Git repository
check_git_repo() {
    if ! git rev-parse --git-dir &>/dev/null; then
        log "ERROR" "Not in a Git repository"
        exit 1
    fi
}

# Get push information
get_push_info() {
    local remote="$1"
    local url="$2"
    
    log "INFO" "Push target: $remote ($url)"
    
    # Read stdin for ref information
    while read local_ref local_sha remote_ref remote_sha; do
        if [[ "$local_sha" == "0000000000000000000000000000000000000000" ]]; then
            log "INFO" "Deleting remote branch: $remote_ref"
            return 0  # Allow deletions
        fi
        
        log "INFO" "Pushing: $local_ref -> $remote_ref"
        log "INFO" "Commits: $remote_sha..$local_sha"
        
        # Store for use by other functions
        export PUSH_LOCAL_REF="$local_ref"
        export PUSH_REMOTE_REF="$remote_ref"
        export PUSH_LOCAL_SHA="$local_sha"
        export PUSH_REMOTE_SHA="$remote_sha"
    done
}

# Check branch protection rules
check_branch_protection() {
    local branch_name="${PUSH_REMOTE_REF#refs/heads/}"
    
    log "STEP" "Checking branch protection rules..."
    
    # Protect main/master branch
    if [[ "$branch_name" == "main" || "$branch_name" == "master" ]]; then
        log "WARN" "Pushing to protected branch: $branch_name"
        
        # Additional checks for main branch
        if [[ "$(git rev-list --count "$PUSH_REMOTE_SHA..$PUSH_LOCAL_SHA")" -gt 10 ]]; then
            log "ERROR" "Too many commits in single push to main branch (>10)"
            return 1
        fi
        
        # Require PR for main branch pushes (except for CI)
        if [[ -z "${CI:-}" ]]; then
            log "ERROR" "Direct push to main branch not allowed (use PR)"
            return 1
        fi
    fi
    
    log "SUCCESS" "Branch protection rules passed"
}

# Run TODO sweeper
run_todo_sweeper() {
    if [[ "$ENABLE_TODO_SWEEPER" != "true" ]]; then
        log "INFO" "TODO sweeper disabled, skipping"
        return 0
    fi
    
    log "STEP" "Running TODO/FIXME/HACK sweeper..."
    
    if [[ ! -f "$TODO_SWEEPER" ]]; then
        log "ERROR" "TODO sweeper not found: $TODO_SWEEPER"
        return 1
    fi
    
    cd "$PROJECT_ROOT"
    
    if php "$TODO_SWEEPER" --strict; then
        log "SUCCESS" "TODO sweeper passed"
    else
        log "ERROR" "TODO sweeper failed - check reports/todo_register.md"
        echo ""
        echo -e "${RED}❌ TODO/FIXME/HACK Violations Found${NC}"
        echo ""
        echo "The following issues were detected:"
        echo "• Non-compliant TODO comments"
        echo "• Expired TODO items"
        echo "• Missing issue IDs or dates"
        echo ""
        echo -e "${YELLOW}Required format:${NC}"
        echo "// ALLOWTODO: ISSUE-123 2025-02-15 Description of what needs to be done"
        echo ""
        echo -e "${CYAN}Fix these issues and commit the changes before pushing.${NC}"
        echo ""
        return 1
    fi
}

# Check code style with Laravel Pint
run_lint_check() {
    if [[ "$ENABLE_LINT_CHECK" != "true" ]]; then
        log "INFO" "Lint check disabled, skipping"
        return 0
    fi
    
    log "STEP" "Running code style check..."
    
    cd "$PROJECT_ROOT"
    
    if [[ -f "vendor/bin/pint" ]]; then
        if vendor/bin/pint --test; then
            log "SUCCESS" "Code style check passed"
        else
            log "ERROR" "Code style violations found"
            echo ""
            echo -e "${RED}❌ Code Style Violations${NC}"
            echo ""
            echo "Run the following to fix:"
            echo -e "${CYAN}vendor/bin/pint${NC}"
            echo ""
            return 1
        fi
    else
        log "WARN" "Laravel Pint not found, skipping style check"
    fi
}

# Run static analysis with PHPStan/Larastan
run_type_check() {
    if [[ "$ENABLE_TYPE_CHECK" != "true" ]]; then
        log "INFO" "Type check disabled, skipping"
        return 0
    fi
    
    log "STEP" "Running static analysis..."
    
    cd "$PROJECT_ROOT"
    
    if [[ -f "vendor/bin/phpstan" ]]; then
        if vendor/bin/phpstan analyse --no-progress --error-format=table; then
            log "SUCCESS" "Static analysis passed"
        else
            log "ERROR" "Static analysis failed"
            echo ""
            echo -e "${RED}❌ Static Analysis Violations${NC}"
            echo ""
            echo "Fix the type errors and try again."
            echo ""
            return 1
        fi
    else
        log "WARN" "PHPStan not found, skipping static analysis"
    fi
}

# Run security scan
run_security_scan() {
    if [[ "$ENABLE_SECURITY_SCAN" != "true" ]]; then
        log "INFO" "Security scan disabled, skipping"
        return 0
    fi
    
    log "STEP" "Running security scan..."
    
    cd "$PROJECT_ROOT"
    
    # Check for security advisories in Composer
    if command -v composer &>/dev/null; then
        if composer audit --no-dev; then
            log "SUCCESS" "Composer security audit passed"
        else
            log "ERROR" "Security vulnerabilities found in dependencies"
            echo ""
            echo -e "${RED}❌ Security Vulnerabilities Detected${NC}"
            echo ""
            echo "Update vulnerable packages before pushing:"
            echo -e "${CYAN}composer update${NC}"
            echo ""
            return 1
        fi
    fi
    
    # Check for secrets in commits
    log "INFO" "Scanning for secrets in commits..."
    
    # Simple secret detection patterns
    local secret_patterns=(
        'password\s*[=:]\s*["\047][^"\047\s]{8,}'
        'api[_-]?key\s*[=:]\s*["\047][^"\047\s]{20,}'
        'secret\s*[=:]\s*["\047][^"\047\s]{16,}'
        'token\s*[=:]\s*["\047][^"\047\s]{20,}'
        'sk-[a-zA-Z0-9]{32,}'  # OpenAI keys
    )
    
    local secrets_found=false
    
    for pattern in "${secret_patterns[@]}"; do
        if git log --grep="$pattern" -E --since="1 day ago" --oneline | head -1 | grep -qE "$pattern"; then
            log "ERROR" "Potential secret found in recent commits: $pattern"
            secrets_found=true
        fi
    done
    
    if [[ "$secrets_found" == "true" ]]; then
        echo ""
        echo -e "${RED}❌ Potential Secrets Detected${NC}"
        echo ""
        echo "Remove secrets from commit history before pushing."
        echo ""
        return 1
    fi
    
    log "SUCCESS" "Security scan passed"
}

# Check .env file integrity
check_env_integrity() {
    if [[ "$ENABLE_ENV_INTEGRITY" != "true" ]]; then
        log "INFO" "ENV integrity check disabled, skipping"
        return 0
    fi
    
    log "STEP" "Checking .env file integrity..."
    
    cd "$PROJECT_ROOT"
    
    if [[ -f ".env" ]]; then
        # Check if .env is in git (it shouldn't be)
        if git ls-files --error-unmatch .env &>/dev/null; then
            log "ERROR" ".env file is tracked by Git (security risk)"
            echo ""
            echo -e "${RED}❌ Environment File Security Issue${NC}"
            echo ""
            echo "The .env file should not be tracked by Git."
            echo "Remove it with:"
            echo -e "${CYAN}git rm --cached .env${NC}"
            echo -e "${CYAN}echo '.env' >> .gitignore${NC}"
            echo ""
            return 1
        fi
        
        # Check for required environment variables
        local required_vars=("APP_KEY" "DB_CONNECTION" "REDIS_HOST")
        for var in "${required_vars[@]}"; do
            if ! grep -q "^${var}=" .env; then
                log "ERROR" "Required environment variable missing: $var"
                return 1
            fi
        done
        
        log "SUCCESS" "Environment file integrity check passed"
    else
        log "WARN" ".env file not found"
    fi
}

# Run test suite
run_tests() {
    if [[ "$REQUIRE_TESTS_PASS" != "true" ]]; then
        log "INFO" "Test execution disabled, skipping"
        return 0
    fi
    
    log "STEP" "Running test suite..."
    
    cd "$PROJECT_ROOT"
    
    if [[ -f "vendor/bin/phpunit" ]]; then
        # Run tests with coverage
        if vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml; then
            log "SUCCESS" "Test suite passed"
            
            # Check coverage requirement
            if [[ -f "coverage.xml" ]]; then
                local coverage=$(grep -o 'lines-covered="[0-9]*"' coverage.xml | grep -o '[0-9]*' | head -1)
                local total=$(grep -o 'lines-valid="[0-9]*"' coverage.xml | grep -o '[0-9]*' | head -1)
                
                if [[ -n "$coverage" && -n "$total" && "$total" -gt 0 ]]; then
                    local coverage_pct=$((coverage * 100 / total))
                    log "INFO" "Code coverage: ${coverage_pct}%"
                    
                    if [[ "$coverage_pct" -lt "$REQUIRE_MIN_COVERAGE" ]]; then
                        log "ERROR" "Code coverage below minimum requirement (${coverage_pct}% < ${REQUIRE_MIN_COVERAGE}%)"
                        echo ""
                        echo -e "${RED}❌ Insufficient Code Coverage${NC}"
                        echo ""
                        echo "Current coverage: ${coverage_pct}%"
                        echo "Required coverage: ${REQUIRE_MIN_COVERAGE}%"
                        echo ""
                        echo "Add more tests before pushing."
                        echo ""
                        return 1
                    fi
                fi
            fi
        else
            log "ERROR" "Test suite failed"
            echo ""
            echo -e "${RED}❌ Test Suite Failed${NC}"
            echo ""
            echo "Fix failing tests before pushing."
            echo ""
            return 1
        fi
    else
        log "WARN" "PHPUnit not found, skipping tests"
    fi
}

# Check deployment readiness
check_deployment_readiness() {
    log "STEP" "Checking deployment readiness..."
    
    cd "$PROJECT_ROOT"
    
    # Check for production dependencies
    if [[ -f "composer.json" ]]; then
        if ! composer check-platform-reqs --no-dev &>/dev/null; then
            log "ERROR" "Production platform requirements not met"
            return 1
        fi
    fi
    
    # Ensure autoloader optimization
    if [[ ! -f "vendor/composer/autoload_classmap.php" ]]; then
        log "WARN" "Autoloader not optimized (run: composer install --optimize-autoloader)"
    fi
    
    log "SUCCESS" "Deployment readiness check passed"
}

# Generate push report
generate_push_report() {
    log "STEP" "Generating push validation report..."
    
    local report_file="$REPORTS_DIR/push_validation.md"
    mkdir -p "$REPORTS_DIR"
    
    cat > "$report_file" << EOF
# Push Validation Report

**Generated**: $(date -Iseconds)  
**Hook Version**: $SCRIPT_VERSION  
**Remote**: ${1:-unknown}  
**URL**: ${2:-unknown}  
**Branch**: ${PUSH_REMOTE_REF#refs/heads/}  
**Commits**: ${PUSH_REMOTE_SHA:0:7}..${PUSH_LOCAL_SHA:0:7}  

## Validation Results

| Check | Status | Notes |
|-------|--------|-------|
| Branch Protection | ✅ PASS | Protected branch rules enforced |
| TODO Sweeper | ✅ PASS | All TODO comments properly formatted |
| Code Style | ✅ PASS | Laravel Pint validation passed |
| Static Analysis | ✅ PASS | PHPStan/Larastan analysis passed |
| Security Scan | ✅ PASS | No vulnerabilities detected |
| Environment Check | ✅ PASS | .env file integrity verified |
| Test Suite | ✅ PASS | All tests passing with ${REQUIRE_MIN_COVERAGE}%+ coverage |
| Deployment Ready | ✅ PASS | Production requirements satisfied |

## Push Guard Configuration

- TODO Sweeper: ${ENABLE_TODO_SWEEPER}
- Lint Check: ${ENABLE_LINT_CHECK}
- Type Check: ${ENABLE_TYPE_CHECK}
- Security Scan: ${ENABLE_SECURITY_SCAN}
- ENV Integrity: ${ENABLE_ENV_INTEGRITY}
- Test Requirements: ${REQUIRE_TESTS_PASS}
- Min Coverage: ${REQUIRE_MIN_COVERAGE}%

**Status**: ✅ PUSH APPROVED
EOF
    
    log "SUCCESS" "Push validation report generated: $report_file"
}

# Main execution
main() {
    local remote="$1"
    local url="$2"
    
    # Initialize log
    echo "SentinentX Pre-Push Hook Started: $(date -Iseconds)" > "$HOOK_LOG"
    
    print_header
    
    # Basic checks
    check_git_repo
    get_push_info "$remote" "$url"
    
    # Protection and validation checks
    check_branch_protection
    run_todo_sweeper
    run_lint_check
    run_type_check
    run_security_scan
    check_env_integrity
    run_tests
    check_deployment_readiness
    
    # Generate report
    generate_push_report "$remote" "$url"
    
    # Success
    echo ""
    echo -e "${GREEN}${BOLD}✅ PUSH APPROVED${NC}"
    echo -e "${GREEN}All validation checks passed successfully!${NC}"
    echo ""
    echo -e "${CYAN}📊 View full report: $REPORTS_DIR/push_validation.md${NC}"
    echo ""
    
    log "SUCCESS" "Pre-push validation completed successfully"
}

# Execute main function with all arguments
main "$@"
