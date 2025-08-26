# Contributing to SentinentX

Thank you for your interest in contributing to SentinentX! This document provides guidelines and information for contributors.

## 🌟 Ways to Contribute

- **Bug Reports**: Report bugs with detailed reproduction steps
- **Feature Requests**: Suggest new features or improvements
- **Code Contributions**: Submit bug fixes, features, or optimizations
- **Documentation**: Improve documentation, examples, or guides
- **Testing**: Help with testing on different environments
- **Security**: Report security vulnerabilities responsibly

## 🚀 Getting Started

### Development Environment Setup

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/your-username/sentinentx.git
   cd sentinentx
   ```

3. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

4. **Set up environment**:
   ```bash
   cp env.example.template .env
   php artisan key:generate
   ```

5. **Run tests** to ensure everything works:
   ```bash
   php artisan test
   ```

### Development Workflow

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards
3. **Add tests** for new functionality
4. **Run the test suite**:
   ```bash
   php artisan test
   php vendor/bin/pint --test
   php vendor/bin/phpstan analyze
   ```

5. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Add feature: your feature description"
   ```

6. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

7. **Create a Pull Request** on GitHub

## 📋 Code Standards

### PHP Coding Standards

We follow **PSR-12** coding standards:

```bash
# Check code style
php vendor/bin/pint --test

# Fix code style automatically
php vendor/bin/pint
```

### Static Analysis

We use **PHPStan** for static analysis:

```bash
# Run static analysis
php vendor/bin/phpstan analyze

# Fix issues where possible
php vendor/bin/phpstan analyze --fix
```

### Code Quality Requirements

- **Type Declarations**: Use strict typing (`declare(strict_types=1);`)
- **Return Types**: All methods must have return type declarations
- **PHPDoc**: Document all public methods and complex logic
- **Error Handling**: Proper exception handling and logging
- **Testing**: Comprehensive test coverage for new features

### Example Code Structure

```php
<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\Trade;
use App\Exceptions\TradingException;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing trading positions
 */
final class PositionService
{
    /**
     * Create a new trading position
     *
     * @param array<string, mixed> $params
     * @return Trade
     * @throws TradingException
     */
    public function createPosition(array $params): Trade
    {
        try {
            // Implementation here
            $trade = Trade::create($params);
            
            Log::info('Position created', ['trade_id' => $trade->id]);
            
            return $trade;
        } catch (\Exception $e) {
            Log::error('Failed to create position', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            throw new TradingException(
                'Failed to create position: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
```

## 🧪 Testing Guidelines

### Test Types

1. **Unit Tests**: Test individual classes/methods
2. **Feature Tests**: Test HTTP endpoints and workflows
3. **Integration Tests**: Test service integrations
4. **Performance Tests**: Test performance requirements

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Trading;

use App\Services\Trading\PositionService;
use App\Models\Trade;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PositionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_position_successfully(): void
    {
        $service = new PositionService();
        
        $params = [
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'quantity' => 0.1,
        ];
        
        $trade = $service->createPosition($params);
        
        $this->assertInstanceOf(Trade::class, $trade);
        $this->assertEquals('BTCUSDT', $trade->symbol);
        $this->assertDatabaseHas('trades', ['id' => $trade->id]);
    }
}
```

### Test Requirements

- **Coverage**: Aim for >90% test coverage on new code
- **Assertions**: Use meaningful assertions with clear failure messages
- **Data**: Use factories for test data creation
- **Isolation**: Each test should be independent
- **Performance**: Keep tests fast (unit tests <100ms)

## 🔒 Security Guidelines

### Security Best Practices

- **Input Validation**: Validate all user inputs
- **SQL Injection**: Use Eloquent ORM or prepared statements
- **XSS Prevention**: Escape output appropriately
- **Authentication**: Use Laravel's built-in auth mechanisms
- **Authorization**: Implement proper access controls
- **Secrets**: Never commit API keys or passwords

### Reporting Security Issues

**DO NOT** open GitHub issues for security vulnerabilities.

Instead, email us at: **security@sentinentx.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We'll respond within 24 hours and work with you to resolve the issue.

## 📚 Documentation Standards

### Code Documentation

- **PHPDoc**: Document all public methods
- **Inline Comments**: Explain complex logic
- **README Updates**: Update README for new features
- **API Documentation**: Document new API endpoints

### Documentation Example

```php
/**
 * Calculate optimal leverage based on risk profile and AI confidence
 *
 * @param User $user The user making the trade
 * @param array<string, mixed> $aiDecision AI decision data
 * @param string $symbol Trading pair symbol
 * @param float $accountBalance Account balance in USD
 * @return array<string, mixed> Leverage calculation result
 * 
 * @throws InvalidArgumentException When user has no risk profile
 * @throws TradingException When calculation fails
 */
public function calculateOptimalLeverage(
    User $user,
    array $aiDecision,
    string $symbol,
    float $accountBalance
): array {
    // Implementation
}
```

## 🔄 Pull Request Process

### Before Submitting

1. **Rebase** your branch on the latest `main`
2. **Run all tests** and ensure they pass
3. **Update documentation** if needed
4. **Check code style** with Pint
5. **Run static analysis** with PHPStan

### Pull Request Template

When creating a PR, include:

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Verified on testnet (for trading features)

## Checklist
- [ ] Code follows PSR-12 standards
- [ ] PHPStan analysis passes
- [ ] Documentation updated
- [ ] No sensitive data committed
```

### Review Process

1. **Automated Checks**: CI/CD pipeline runs tests and checks
2. **Code Review**: Maintainers review code quality and functionality
3. **Testing**: Manual testing on testnet if applicable
4. **Approval**: At least one maintainer approval required
5. **Merge**: Squash and merge into main branch

## 🏗️ Architecture Guidelines

### Service Layer

- **Single Responsibility**: Each service has one clear purpose
- **Dependency Injection**: Use constructor injection
- **Interface Contracts**: Define interfaces for external dependencies
- **Error Handling**: Consistent exception handling

### Database Design

- **Migrations**: All schema changes via migrations
- **Indexes**: Add appropriate database indexes
- **Foreign Keys**: Maintain referential integrity
- **Tenant Isolation**: Consider multi-tenant implications

### API Design

- **RESTful**: Follow REST conventions
- **Versioning**: Use API versioning (`/api/v1/`)
- **Response Format**: Consistent JSON response structure
- **Error Handling**: Proper HTTP status codes and error messages

## 🌍 Internationalization

### Adding New Languages

1. **Create language files** in `resources/lang/{locale}/`
2. **Use translation helpers** in code: `__('message.key')`
3. **Test translations** with different locales
4. **Update documentation** with supported languages

### Translation Guidelines

- **Keys**: Use descriptive, hierarchical keys
- **Placeholders**: Use named placeholders (`:name`)
- **Pluralization**: Handle plural forms properly
- **Context**: Provide context for translators

## 📊 Performance Guidelines

### Performance Requirements

- **API Response**: <500ms for API endpoints
- **Database Queries**: Minimize N+1 queries
- **Caching**: Use Redis for frequently accessed data
- **Memory Usage**: Efficient memory management
- **Concurrent Users**: Support multiple simultaneous users

### Optimization Techniques

- **Database Indexing**: Add indexes for query optimization
- **Eager Loading**: Use Eloquent eager loading
- **Query Optimization**: Optimize complex queries
- **Caching Strategy**: Cache expensive operations
- **Background Jobs**: Use queues for heavy processing

## 🐛 Bug Reports

### Bug Report Template

When reporting bugs, include:

```markdown
## Bug Description
Clear description of the bug

## Steps to Reproduce
1. Step one
2. Step two
3. Step three

## Expected Behavior
What you expected to happen

## Actual Behavior
What actually happened

## Environment
- PHP Version:
- Laravel Version:
- Operating System:
- Browser (if applicable):

## Additional Context
Any additional context, screenshots, or logs
```

### Priority Levels

- **Critical**: Security issues, data loss, system crashes
- **High**: Major functionality broken
- **Medium**: Minor functionality issues
- **Low**: Cosmetic issues, enhancement requests

## 🎯 Feature Requests

### Feature Request Template

```markdown
## Feature Description
Clear description of the proposed feature

## Use Case
Why is this feature needed?

## Proposed Solution
How should this feature work?

## Alternatives Considered
What alternatives have you considered?

## Additional Context
Any additional context or mockups
```

## 📞 Community Guidelines

### Code of Conduct

- **Be Respectful**: Treat all contributors with respect
- **Be Constructive**: Provide helpful, constructive feedback
- **Be Collaborative**: Work together towards common goals
- **Be Patient**: Help newcomers learn our processes

### Communication Channels

- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: General questions and discussions
- **Email**: security@sentinentx.com for security issues

## 🏆 Recognition

### Contributors

We recognize contributors in several ways:

- **Contributor List**: Listed in README and releases
- **Special Thanks**: Mentioned in release notes
- **Badges**: GitHub contributor badges
- **Documentation**: Contributors guide featuring best practices

### Types of Contributions

All contributions are valuable:
- **Code**: Bug fixes, features, optimizations
- **Documentation**: Guides, examples, API docs
- **Testing**: Bug reports, test cases, QA
- **Community**: Helping others, mentoring
- **Design**: UI/UX improvements, graphics

## 📝 License

By contributing to SentinentX, you agree that your contributions will be licensed under the MIT License.

## 🙏 Thank You

Thank you for contributing to SentinentX! Your efforts help make this project better for everyone in the crypto trading community.

---

For questions about contributing, please open a GitHub Discussion or contact us at contributors@sentinentx.com.
