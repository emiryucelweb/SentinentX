#!/bin/bash

# Enhanced Quick Install Script Test Suite
# Test all functions and error conditions

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Test logging
test_log() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

test_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

test_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
}

# Test system requirements
test_system_requirements() {
    test_log "Testing system requirements check..."
    
    # Test architecture detection
    local arch
    arch=$(uname -m)
    if [[ "$arch" == "x86_64" ]]; then
        test_pass "Architecture check: $arch"
    else
        test_fail "Architecture check: $arch (not x86_64)"
    fi
    
    # Test disk space
    local available_space
    available_space=$(df / | awk 'NR==2 {print int($4/1024/1024)}')
    if [[ $available_space -ge 10 ]]; then
        test_pass "Disk space: ${available_space}GB available"
    else
        test_fail "Disk space: ${available_space}GB (insufficient)"
    fi
    
    # Test RAM
    local available_ram
    available_ram=$(free -m | awk 'NR==2 {print $7}')
    if [[ $available_ram -ge 2048 ]]; then
        test_pass "RAM: ${available_ram}MB available"
    else
        test_fail "RAM: ${available_ram}MB (insufficient)"
    fi
}

# Test Ubuntu version detection
test_ubuntu_version() {
    test_log "Testing Ubuntu version detection..."
    
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        if [[ "$ID" == "ubuntu" ]]; then
            test_pass "Ubuntu detected: $PRETTY_NAME"
            
            local major_version minor_version
            major_version=$(echo "$VERSION_ID" | cut -d. -f1)
            minor_version=$(echo "$VERSION_ID" | cut -d. -f2)
            
            if [[ $major_version -ge 22 ]]; then
                test_pass "Version check: $VERSION_ID (supported)"
                
                if [[ $major_version -eq 24 && $minor_version -eq 4 ]]; then
                    test_pass "Optimal version: Ubuntu 24.04 LTS"
                elif [[ $major_version -eq 22 && $minor_version -eq 4 ]]; then
                    test_pass "Good version: Ubuntu 22.04 LTS"
                fi
            else
                test_fail "Version check: $VERSION_ID (unsupported)"
            fi
        else
            test_fail "OS check: $ID (not Ubuntu)"
        fi
    else
        test_fail "OS release file not found"
    fi
}

# Test network connectivity
test_network() {
    test_log "Testing network connectivity..."
    
    local test_urls=(
        "google.com"
        "ubuntu.com"
        "github.com"
    )
    
    local connected=false
    for url in "${test_urls[@]}"; do
        if ping -c 1 -W 5 "$url" &>/dev/null; then
            test_pass "Network: $url reachable"
            connected=true
            break
        fi
    done
    
    if [[ "$connected" == false ]]; then
        test_fail "Network: No connectivity"
    fi
}

# Test package manager
test_package_manager() {
    test_log "Testing package manager..."
    
    # Check if apt is available
    if command -v apt &> /dev/null; then
        test_pass "Package manager: apt available"
    else
        test_fail "Package manager: apt not found"
    fi
    
    # Check for locks
    if lsof /var/lib/dpkg/lock-frontend &>/dev/null; then
        test_fail "Package manager: locked"
    else
        test_pass "Package manager: not locked"
    fi
    
    # Test update capability (read-only)
    if apt list --upgradable &>/dev/null; then
        test_pass "Package manager: functional"
    else
        test_fail "Package manager: not functional"
    fi
}

# Test existing services
test_existing_services() {
    test_log "Testing existing services..."
    
    local conflicting_services=(
        "apache2"
        "mysql"
        "nginx"
    )
    
    for service in "${conflicting_services[@]}"; do
        if systemctl is-active --quiet "$service" 2>/dev/null; then
            test_fail "Conflicting service active: $service"
        else
            test_pass "Service check: $service not active"
        fi
    done
}

# Test PHP prerequisites
test_php_prerequisites() {
    test_log "Testing PHP prerequisites..."
    
    # Check if repository tools are available
    if command -v add-apt-repository &> /dev/null; then
        test_pass "Repository tools: available"
    else
        test_fail "Repository tools: missing"
    fi
    
    # Check GPG tools
    if command -v gpg &> /dev/null; then
        test_pass "GPG tools: available"
    else
        test_fail "GPG tools: missing"
    fi
}

# Test script syntax
test_script_syntax() {
    test_log "Testing script syntax..."
    
    if [[ -f "quick_vds_install.sh" ]]; then
        if bash -n quick_vds_install.sh; then
            test_pass "Script syntax: valid"
        else
            test_fail "Script syntax: invalid"
        fi
    else
        test_fail "Script file not found"
    fi
}

# Test script permissions
test_script_permissions() {
    test_log "Testing script permissions..."
    
    if [[ -f "quick_vds_install.sh" ]]; then
        if [[ -x "quick_vds_install.sh" ]]; then
            test_pass "Script permissions: executable"
        else
            test_fail "Script permissions: not executable"
        fi
    else
        test_fail "Script file not found"
    fi
}

# Main test function
main() {
    echo "🧪 Enhanced Installation Script Test Suite"
    echo "=========================================="
    echo "Testing system compatibility and requirements..."
    echo ""
    
    test_script_syntax
    test_script_permissions
    test_system_requirements
    test_ubuntu_version
    test_network
    test_package_manager
    test_existing_services
    test_php_prerequisites
    
    echo ""
    echo "🏁 Test suite completed!"
    echo "Review any failed tests before running installation."
}

# Run tests
main "$@"
