# Contributing to Laravel Nepali Payment

First off, thank you for considering contributing to this package! It's people like you that make this package better for everyone.

## 📋 Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Commit Message Guidelines](#commit-message-guidelines)

## 🚀 Getting Started

### Prerequisites

- PHP >= 8.1
- Composer >= 2.0
- SQLite (for testing)
- Git

### Development Setup

1. **Fork the Repository**
   
   Click the "Fork" button on GitHub to create your own copy of the repository.

2. **Clone Your Fork**
   ```bash
   git clone https://github.com/aryanmalla19/laravel-nepali-payment.git
   cd laravel-nepali-payment
   ```

3. **Install Dependencies**
   ```bash
   composer install
   ```

4. **Run Tests**
   ```bash
   ./vendor/bin/phpunit
   ```

## 🎨 Coding Standards

We use **Laravel Pint** for code formatting and **PHPStan** for static analysis.

### Code Style (Laravel Pint)

We follow the [PER Coding Style 2.0](https://www.php-fig.org/per/coding-style/) via Laravel Pint.

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style automatically
./vendor/bin/pint
```

### Static Analysis (PHPStan Level 5)

```bash
# Run static analysis
./vendor/bin/phpstan analyse

# Clear cache and re-analyze
./vendor/bin/phpstan clear-result-cache && ./vendor/bin/phpstan analyse
```

### Key Guidelines

- **PHP Version**: Minimum PHP 8.1
- **Strict Types**: Always declare `declare(strict_types=1);` at the top of PHP files
- **Return Types**: Always declare return types for methods
- **Type Hints**: Use type hints for all parameters
- **PHPDoc**: Document all public methods with PHPDoc blocks
- **Naming Conventions**:
  - Classes: `PascalCase` (e.g., `PaymentManager`)
  - Methods: `camelCase` (e.g., `createPayment`)
  - Constants: `UPPER_CASE` (e.g., `MAX_RETRY_ATTEMPTS`)
  - Variables: `camelCase` (e.g., `$paymentAmount`)
  - Database columns: `snake_case` (e.g., `merchant_reference_id`)

### Architecture Principles

1. **Single Responsibility**: Each class should have one reason to change
2. **Dependency Injection**: Use constructor injection, avoid facades in service classes
3. **Immutable Objects**: Use readonly properties where possible (PHP 8.1+)
4. **Null Safety**: Use null-safe operators (`?->`) and proper null checks
5. **Early Returns**: Prefer early returns over deeply nested conditions

## 🧪 Testing

### Writing Tests

- All new features must include tests
- Maintain or improve current code coverage (target: >85%)
- Test both success and failure scenarios
- Use descriptive test method names following the pattern: `test_method_name_description`

```php
public function test_payment_service_creates_record_with_valid_data()
{
    // Arrange
    $data = [
        'gateway' => 'khalti',
        'amount' => 1000,
    ];
    
    // Act
    $result = $this->service->createPayment(...$data);
    
    // Assert
    $this->assertDatabaseHas('payment_transactions', $data);
    $this->assertInstanceOf(PaymentTransaction::class, $result);
}
```

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run with testdox output (pretty format)
./vendor/bin/phpunit --testdox

# Run with coverage report
./vendor/bin/phpunit --coverage-html coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/Services/PaymentServiceTest.php

# Run specific test method
./vendor/bin/phpunit --filter test_payment_service_creates_record

# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run only feature tests
./vendor/bin/phpunit --testsuite Feature
```

### Pre-commit Checklist

Before committing, run:

```bash
# Fix code style
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse

# Run tests
./vendor/bin/phpunit
```

Or use the convenient composer script:

```bash
composer check
```

## 📤 Pull Request Process

### 1. Create a Branch

```bash
# For new features
git checkout -b feature/your-feature-name

# For bug fixes
git checkout -b fix/issue-description

# For documentation
git checkout -b docs/improve-readme
```

### 2. Make Your Changes

- Write clean, documented code
- Add tests for new functionality
- Update README.md if you're changing the API
- Update CHANGELOG.md (if applicable)

### 3. Run Quality Checks

```bash
# Fix code style
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse

# Run tests
./vendor/bin/phpunit
```

### 4. Commit Your Changes

```bash
git add .
git commit -m "feat: add support for X feature"
```

### 5. Push and Create PR

```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub with a clear description.

### PR Requirements

Before submitting a PR, ensure:

- [ ] Code follows our coding standards (run `./vendor/bin/pint`)
- [ ] Static analysis passes (`./vendor/bin/phpstan analyse`)
- [ ] All tests pass (`./vendor/bin/phpunit`)
- [ ] New features have tests
- [ ] Documentation is updated (if needed)
- [ ] Commit messages follow [Conventional Commits](#commit-message-guidelines)

### PR Review Process

1. A maintainer will review your PR within 48-72 hours
2. Address any requested changes
3. Once approved, a maintainer will merge your PR
4. Your contribution will be recognized in release notes


## 🐛 Reporting Bugs

When reporting bugs, please provide:

1. **PHP Version**: Run `php -v`
2. **Laravel Version**: Run `php artisan --version`
3. **Package Version**: Check your `composer.lock` or `composer show jaap-tech/laravel-nepali-payment`
4. **Steps to Reproduce**: 
   - Clear step-by-step instructions
   - Minimal code example that reproduces the issue
5. **Expected Behavior**: What you expected to happen
6. **Actual Behavior**: What actually happened
7. **Error Messages**: Full stack trace and error messages
8. **Additional Context**: Any other relevant information


## 🙏 Recognition

Contributors will be recognized in:
- README.md contributors section
- Release notes
- GitHub contributors graph

## 📞 Getting Help

- **GitHub Issues**: For bug reports and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: aryanmalla19@gmail.com

---

## Quick Reference

```bash
# Setup
composer install

# Development workflow
./vendor/bin/pint          # Fix code style
./vendor/bin/phpstan analyse  # Static analysis
./vendor/bin/phpunit       # Run tests

# Or use composer scripts
composer check             # Run all checks
composer test              # Run tests
composer format            # Fix code style

# Before PR
git checkout -b feature/my-feature
# ... make changes ...
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/phpunit
git commit -m "feat: add my feature"
git push origin feature/my-feature
```

Thank you for contributing! 🎉

---

**Maintainers**: [@aryanmalla19](https://github.com/aryanmalla19)
