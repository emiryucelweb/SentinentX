#!/bin/bash

# SentinentX Git Hooks Installation Script
# Sets up pre-push hooks and Git configuration for quality gates

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
readonly GIT_HOOKS_DIR="$PROJECT_ROOT/.git/hooks"

# Colors
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly RED='\033[0;31m'
readonly NC='\033[0m'

echo -e "${BLUE}🔧 SentinentX Git Hooks Installation${NC}"
echo "===================================="
echo ""

# Check if we're in a Git repository
if [[ ! -d "$PROJECT_ROOT/.git" ]]; then
    echo -e "${RED}❌ Error: Not in a Git repository${NC}"
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p "$GIT_HOOKS_DIR"

# Install pre-push hook
echo -e "${YELLOW}📋 Installing pre-push hook...${NC}"

if [[ -f "$SCRIPT_DIR/pre-push-hook.sh" ]]; then
    cp "$SCRIPT_DIR/pre-push-hook.sh" "$GIT_HOOKS_DIR/pre-push"
    chmod +x "$GIT_HOOKS_DIR/pre-push"
    echo -e "${GREEN}✅ Pre-push hook installed${NC}"
else
    echo -e "${RED}❌ Error: pre-push-hook.sh not found${NC}"
    exit 1
fi

# Install commit-msg hook for conventional commits (optional)
echo -e "${YELLOW}📋 Installing commit-msg hook...${NC}"

cat > "$GIT_HOOKS_DIR/commit-msg" << 'EOF'
#!/bin/bash

# SentinentX Commit Message Validation
# Enforces conventional commit format

readonly COMMIT_MSG_FILE="$1"
readonly COMMIT_MSG=$(cat "$COMMIT_MSG_FILE")

# Conventional commit pattern
readonly PATTERN='^(feat|fix|docs|style|refactor|test|chore|perf|ci|build|revert)(\(.+\))?: .{1,50}'

if [[ ! "$COMMIT_MSG" =~ $PATTERN ]]; then
    echo ""
    echo "❌ Invalid commit message format!"
    echo ""
    echo "Required format:"
    echo "  <type>[optional scope]: <description>"
    echo ""
    echo "Examples:"
    echo "  feat: add new trading algorithm"
    echo "  fix(auth): resolve login timeout issue"
    echo "  docs: update API documentation"
    echo ""
    echo "Types: feat, fix, docs, style, refactor, test, chore, perf, ci, build, revert"
    echo ""
    exit 1
fi
EOF

chmod +x "$GIT_HOOKS_DIR/commit-msg"
echo -e "${GREEN}✅ Commit-msg hook installed${NC}"

# Configure Git settings for the project
echo -e "${YELLOW}⚙️ Configuring Git settings...${NC}"

# Set up Git configuration for the project
git config --local core.hooksPath .git/hooks
git config --local commit.template "$SCRIPT_DIR/commit-template.txt" 2>/dev/null || true
git config --local push.default simple
git config --local pull.rebase true
git config --local branch.autosetuprebase always

echo -e "${GREEN}✅ Git configuration applied${NC}"

# Create commit template
echo -e "${YELLOW}📝 Creating commit template...${NC}"

cat > "$SCRIPT_DIR/commit-template.txt" << 'EOF'
# <type>[optional scope]: <description>
#
# [optional body]
#
# [optional footer(s)]
#
# Types:
#   feat:     A new feature
#   fix:      A bug fix
#   docs:     Documentation only changes
#   style:    Changes that do not affect the meaning of the code
#   refactor: A code change that neither fixes a bug nor adds a feature
#   test:     Adding missing tests or correcting existing tests
#   chore:    Changes to the build process or auxiliary tools
#   perf:     A code change that improves performance
#   ci:       Changes to CI configuration files and scripts
#   build:    Changes that affect the build system or external dependencies
#   revert:   Reverts a previous commit
#
# Examples:
#   feat: add support for Bitcoin Lightning Network
#   fix(auth): resolve session timeout during trading
#   docs: update API documentation for risk management
#   refactor(trading): optimize position calculation algorithm
EOF

echo -e "${GREEN}✅ Commit template created${NC}"

# Create aliases for common Git operations
echo -e "${YELLOW}🔗 Setting up Git aliases...${NC}"

git config --local alias.st "status"
git config --local alias.co "checkout"
git config --local alias.br "branch"
git config --local alias.ci "commit"
git config --local alias.unstage "reset HEAD --"
git config --local alias.last "log -1 HEAD"
git config --local alias.visual "!gitk"

# SentinentX specific aliases
git config --local alias.feature "!sh -c 'git checkout develop && git pull origin develop && git checkout -b feature/\$1' -"
git config --local alias.hotfix "!sh -c 'git checkout main && git pull origin main && git checkout -b hotfix/\$1' -"
git config --local alias.cleanup "!git branch --merged | grep -v '\\*\\|main\\|develop' | xargs -n 1 git branch -d"
git config --local alias.pushf "push --force-with-lease"

echo -e "${GREEN}✅ Git aliases configured${NC}"

# Test hooks installation
echo -e "${YELLOW}🧪 Testing hook installation...${NC}"

if [[ -x "$GIT_HOOKS_DIR/pre-push" ]]; then
    echo -e "${GREEN}✅ Pre-push hook is executable${NC}"
else
    echo -e "${RED}❌ Pre-push hook is not executable${NC}"
    exit 1
fi

if [[ -x "$GIT_HOOKS_DIR/commit-msg" ]]; then
    echo -e "${GREEN}✅ Commit-msg hook is executable${NC}"
else
    echo -e "${RED}❌ Commit-msg hook is not executable${NC}"
    exit 1
fi

# Create reports directory
mkdir -p "$PROJECT_ROOT/reports"
echo -e "${GREEN}✅ Reports directory created${NC}"

# Final instructions
echo ""
echo -e "${GREEN}🎉 Git hooks installation completed successfully!${NC}"
echo ""
echo -e "${BLUE}📋 What was installed:${NC}"
echo "  • Pre-push hook with comprehensive quality gates"
echo "  • Commit message validation for conventional commits"
echo "  • Git configuration optimized for the project"
echo "  • Helpful Git aliases for common operations"
echo "  • Commit template for consistent messaging"
echo ""
echo -e "${BLUE}🔗 Available Git aliases:${NC}"
echo "  • git st        - git status"
echo "  • git co        - git checkout"
echo "  • git br        - git branch"
echo "  • git ci        - git commit"
echo "  • git feature   - create feature branch from develop"
echo "  • git hotfix    - create hotfix branch from main"
echo "  • git cleanup   - delete merged branches"
echo "  • git pushf     - force push with lease (safer)"
echo ""
echo -e "${BLUE}🚀 Next steps:${NC}"
echo "  1. Test with: git commit -m 'test: verify hooks installation'"
echo "  2. Review commit template: cat scripts/commit-template.txt"
echo "  3. Read Git strategy: docs/GIT_STRATEGY.md"
echo "  4. Configure CI/CD: .github/workflows/comprehensive-ci.yml"
echo ""
echo -e "${YELLOW}💡 Pro tip:${NC} Use 'git feature SENT-123' to create feature branches"
echo ""
