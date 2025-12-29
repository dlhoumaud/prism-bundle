# PrismBundle


[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E5.4%5E6.0%7C%5E7.0-green.svg)](https://symfony.com/)
[![Tests](https://img.shields.io/badge/tests-399%20passed-brightgreen.svg)](https://github.com/dlhoumaud/prism-bundle)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/dlhoumaud/prism-bundle)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR12-0%20error-brightgreen.svg)](https://phpstan.org/)

> 🇫🇷 **Version française** : [README.md](docs/fr/README.md)

Symfony Bundle for managing **functional scenarios** with multi-scope isolation, full traceability, and intelligent purging.

## 🎯 What is it?

A **business context orchestration system** allowing each developer to create isolated, reproducible, and destructible data universes without collision.

### Major Innovations

✅ **Multi-Scope Isolation**: Multiple developers work on the same database without collision  
✅ **Full Traceability**: Pivot table tracking every created data  
✅ **Intelligent Purge**: Deletion in reverse order respecting FKs  
✅ **Custom Pivot**: Track by any column (not just `id`)  
✅ **Hybrid Scenarios**: Combine YAML (declarative) + PHP (imperative)  
✅ **Modular Imports**: Compose complex scenarios from modules  
✅ **Hexagonal Architecture**: Domain/Application/Infrastructure  


## 📦 Installation

### Installation via Git Repository (recommended)

Once the bundle is published on GitHub, add the VCS repository to `composer.json`:

**Step 1: Configure the Git repository in `composer.json`**

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/dlhoumaud/prism-bundle.git"
        }
    ],
    "require": {
        "prism/bundle": "main"
    }
}
```

**Step 2: Install the bundle**

```bash
composer require prism/bundle:main
```

> 💡 **Tip**: Once tagged versions (v1.0.0, v1.1.0, etc.) are available, you can use:
> ```bash
> composer require prism/bundle:^1.0
> ```

**Step 3: Automatic configuration**

Symfony Flex will automatically configure:
- `config/bundles.php`: Add `Prism\PrismBundle::class`
- `config/packages/prism.yaml`: Default configuration

**Step 4: Create the prism folder**

```bash
mkdir prism
```

**Step 5: Verify installation**

```bash
php bin/console app:prism:list
```

---

### Installation via Path Repository (local development)

**Step 1: Copy the local recipe** (for auto-configuration)

```bash
cp -r PrismBundle/recipes/prism-bundle config/recipes/
```

**Step 2: Add the repository to `composer.json`**

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./PrismBundle"
        }
    ],
    "require": {
        "prism/bundle": "@dev"
    }
}
```

**Step 3: Install the bundle**

```bash
composer update prism/bundle
```

> ℹ️ Symfony Flex will automatically add `Prism\PrismBundle::class` to `config/bundles.php`

**Step 4: Create the configuration `config/packages/prism.yaml`**

```yaml
prism:
    enabled: '%kernel.debug%'
    yaml_path: '%kernel.project_dir%/prism/yaml'
    scripts_path: '%kernel.project_dir%/prism/scripts'
```

**Step 5: Create folders and copy examples**

```bash
# Create folders
mkdir -p prism/yaml prism/scripts

# Copy example files from recipe
cp PrismBundle/recipes/prism-bundle/1.0/prism/yaml/*.yaml.dist prism/yaml/
cp PrismBundle/recipes/prism-bundle/1.0/prism/scripts/*.php.dist prism/scripts/

# Remove .dist extension to activate examples
for f in prism/yaml/*.dist; do mv "$f" "${f%.dist}"; done
for f in prism/scripts/*.dist; do mv "$f" "${f%.dist}"; done
```

**Step 6: Configure auto-discovery of PHP scenarios**

Create `config/services/prism_scenarios.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Auto-discovery of PHP scenarios in App\Prism
    App\Prism\:
        resource: '../../../prism/scripts/'
        tags: ['prism.scenario']
```

> ⚠️ **Note**: Uncomment this section only when you have PHP scenarios in `prism/scripts/`

**Step 7: Clear cache and restart**

```bash
rm -rf var/cache/*
# If using Docker
docker compose restart php
```

**Step 8: Verify installation**

```bash
php bin/console app:prism:list
```

> ℹ️ **Note**: The `prism_resource` table is automatically created on first use (like `doctrine_migration_versions`). No manual migration needed.

### Installation via Packagist (production)

Once the bundle is published on Packagist:

```bash
composer require prism/bundle
```

Symfony Flex will automatically configure the bundle.

## 🗑️ Uninstallation

**Step 1: Remove the bundle from `config/bundles.php`**

Delete the line:
```php
Prism\PrismBundle::class => ['all' => true],
```

**Step 2: Delete the configuration**

```bash
rm config/packages/prism.yaml
```

**Step 3: Remove from `composer.json`**

Delete from the `require` section:
```json
"prism/bundle": "@dev"
```

And from the `repositories` section:
```json
{
    "type": "path",
    "url": "./PrismBundle"
}
```

**Step 4: Delete the local recipe** (if local installation)

```bash
rm -rf config/recipes/prism-bundle
```

**Step 5: Uninstall via Composer**

```bash
composer update --no-scripts
rm -rf var/cache/*
```

**Step 6 (optional): Drop the tracking table**

If you no longer want to keep traces:

```sql
DROP TABLE IF EXISTS prism_resource;
```

## 🚀 Quick Usage

See [Quick Start Guide](docs/en/PRISM_QUICKSTART.md) for a complete guide.

```bash
# List scenarios
php bin/console app:prism:list

# Load a scenario
php bin/console app:prism:load test_users --scope=dev_alice

# Purge a scenario
php bin/console app:prism:purge test_users --scope=dev_alice

# Purge all scenarios of a scope
php bin/console app:prism:purge --scope=dev_alice --all
```

## 📝 Create Your First Scenario

### YAML (simple)

Create `prism/my_prism.yaml`:

```yaml
load:
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@example.com"
      password: "{{ hash('password123') }}"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

Load it:

```bash
php bin/console app:prism:load my_prism --scope=test
```

### PHP (complex logic)

Create `prism/scripts/MyPrismPrism.php`:

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class MyPrismPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('my_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Insert and track automatically
        $userId = $this->insertAndTrack('users', [
            'username' => sprintf('user_%s', $scopeStr),
            'email' => sprintf('user_%s@example.com', $scopeStr),
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('User created: {id}', ['id' => $userId]);
    }
}
```

## 🔥 Main Features

For more details, see the [complete documentation](docs/en/PRISM.md).

### Multi-Scope Isolation

```bash
# Alice, Bob and QA work in parallel without collision
php bin/console app:prism:load chat --scope=dev_alice
php bin/console app:prism:load chat --scope=dev_bob
php bin/console app:prism:load chat --scope=qa_sprint_42
```

### Multi-Database Support

See [Multi-Database Guide](docs/en/MULTI_DATABASE.md).

```yaml
load:
  - table: users
    data:
      username: "admin_{{ scope }}"
  
  - table: audit_logs
    db: hexagonal_secondary  # Secondary database
    data:
      action: "user_created"
```

### Dynamic Lookups (FK)

```yaml
- table: posts
  data:
    author_id:
      table: users
      where:
        username: "admin_{{ scope }}"
      return: id
```

### 44 Faker Data Types

```yaml
data:
  email: "{{ fake(email) }}"
  phone: "{{ fake(phone_fr) }}"
  iban: "{{ fake(iban_fr) }}"
  siret: "{{ fake(siret) }}"
```

See the [complete list in PRISM_YAML.md](docs/en/PRISM_YAML.md#-44-faker-data-types).

## 📚 Documentation

### 🇬🇧 English

- **[Quick Start Guide](docs/en/PRISM_QUICKSTART.md)** - Get started in 2 minutes
- **[Complete Documentation](docs/en/PRISM.md)** - Full reference
- **[Faker Generator](docs/en/PRISM_FAKER.md)** - 44 data types (YAML + PHP)
- **[YAML Scenarios](docs/en/PRISM_YAML.md)** - Variables, lookups, pipes
- **[PHP Scenarios](docs/en/PRISM_PHP.md)** - AbstractPrism API, methods
- **[Hybrid Scenarios](docs/en/PRISM_HYBRID.md)** - YAML + PHP
- **[Multi-Database](docs/en/MULTI_DATABASE.md)** - Multiple databases
- **[Overview](docs/en/PRISM_OVERVIEW.md)** - Architecture and workflow

### 🇫🇷 Français

- **[Guide de démarrage rapide](docs/fr/PRISM_QUICKSTART.md)** - Commencer en 2 minutes
- **[Documentation complète](docs/fr/PRISM.md)** - Référence complète
- **[Générateur Faker](docs/fr/PRISM_FAKER.md)** - 44 types de données (YAML + PHP)
- **[Scénarios YAML](docs/fr/PRISM_YAML.md)** - Variables, lookups, pipes
- **[Scénarios PHP](docs/fr/PRISM_PHP.md)** - API AbstractPrism, méthodes
- **[Scénarios Hybrides](docs/fr/PRISM_HYBRID.md)** - YAML + PHP
- **[Multi-Database](docs/fr/MULTI_DATABASE.md)** - Plusieurs bases de données
- **[Vue d'ensemble](docs/fr/PRISM_OVERVIEW.md)** - Architecture et workflow

## 📊 Comparison

| Feature | Doctrine Fixtures | Alice (Nelmio) | Foundry | Laravel Seeders | **Prism** |
|---------|-------------------|----------------|---------|-----------------|-----------|
| Multi-scope isolation | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Full traceability | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Intelligent purge | ❌ | ❌ | ⚠️ Basic | ❌ | ✅ **UNIQUE** |
| Custom pivot | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Multi-database support | ❌ | ❌ | ⚠️ Complex | ❌ | ✅ **UNIQUE** |
| Hybrid scenarios | ❌ | ⚠️ YAML+Faker | ✅ PHP Factories | ❌ | ✅ YAML+PHP **UNIQUE** |
| Modular imports | ❌ | ❌ | ⚠️ Stories | ❌ | ✅ **UNIQUE** |
| Global YAML variables | ❌ | ⚠️ Parameters | ❌ | ❌ | ✅ vars + $varname |
| Temporary variables | ❌ | ❌ | ✅ Factory states | ❌ | ✅ **UNIQUE** |
| Placeholders (10+ types) | ❌ | ⚠️ 3-4 types | ❌ | ❌ | ✅ scope, hash, env, now, date, uuid, math, **fake**... |
| Auto FK lookups | ⚠️ Manual | ✅ References | ✅ Relations | ⚠️ Manual | ✅ Dynamic lookups |
| Pipes/Transformers | ❌ | ⚠️ Faker formatters | ⚠️ afterInstantiate | ❌ | ✅ 6 chainable pipes |
| Math engine | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Random data | ❌ | ✅ Faker | ✅ Faker | ✅ Faker | ✅ **46 types, 0 dependency** |
| Hexagonal architecture | ❌ | ❌ | ❌ | ❌ | ✅ Domain/App/Infra |
| Unit tests | ⚠️ Basic | ⚠️ Limited | ⚠️ Limited | ⚠️ Basic | ✅ 399 tests (PHPStan 9) | 

## ✅ Tests and Quality

The bundle comes with a **complete test suite**:

- **399 unit tests** with **952 assertions**
- **100% coverage** (Classes, Methods, Lines)
- **0 mocks**: use of FakeRepositories for pure tests
- **27 test files** organized by layer (Domain/Application/Infrastructure)
- **65 analyzed files** (38 src + 27 tests)

### 🚀 Complete Quality Check (Recommended)

**Single command to verify everything** (source code + tests):

```bash
# From the bundle directory
vendor/bin/phpcs src tests --standard=phpcs.xml.dist && \
vendor/bin/phpstan analyse src -c phpstan.neon --level=9 --memory-limit=256M && \
vendor/bin/phpstan analyse tests -c phpstan.neon --level=9 --memory-limit=256M && \
vendor/bin/phpunit -c phpunit.xml.dist --no-coverage

# From the root project with Docker (⭐ RECOMMENDED)
docker compose exec php vendor/bin/phpcs PrismBundle/src PrismBundle/tests --standard=PrismBundle/phpcs.xml.dist && \
docker compose exec php vendor/bin/phpstan analyse PrismBundle/src -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M && \
docker compose exec php vendor/bin/phpstan analyse PrismBundle/tests -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M && \
docker compose exec php vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --no-coverage

# Generate HTML report with PCOV
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-html PrismBundle/var/report

# Generate text report in terminal
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-text
```

**This check verifies:**
- ✅ **PHPCS**: PSR-12 standards on 65 files (src + tests)
- ✅ **PHPStan src**: Static analysis level 9 on source code (38 files)
- ✅ **PHPStan tests**: Static analysis level 9 on tests (27 files)
- ✅ **PHPUnit**: Execution of 399 tests with 952 assertions

**Expected result:**
```
✅ PHPCS: 0 violation on 65 files
✅ PHPStan src: 0 error on 38 files
✅ PHPStan tests: 0 error on 27 files
✅ PHPUnit: 399/399 tests passing, 952 assertions, 100% coverage
```

---

### 🧪 Unit Tests

From the bundle directory:

```bash
# Install development dependencies
cd PrismBundle
composer install

# Run all tests
vendor/bin/phpunit -c phpunit.xml.dist

# Run tests without coverage (faster)
vendor/bin/phpunit -c phpunit.xml.dist --no-coverage

# Run with code coverage (100%)
php -d pcov.directory=. vendor/bin/phpunit -c phpunit.xml.dist --coverage-text

# Run a specific test file
vendor/bin/phpunit -c phpunit.xml.dist tests/Application/YamlPrismTest.php

# Run a specific test
vendor/bin/phpunit -c phpunit.xml.dist --filter testLoadShouldHandleHashWithScope

# Run pipe tests only
vendor/bin/phpunit -c phpunit.xml.dist --filter Pipe
```

From the root project directory (with Docker):

```bash
# Run all tests
docker compose exec php vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --no-coverage

# Run with code coverage (100%)
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-text

# Run a specific test file
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist PrismBundle/tests/Application/YamlPrismTest.php

# Run pipe tests
docker compose exec php vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --filter Pipe --no-coverage
```

---

### 📋 Code Style Check (PHPCS)

```bash
# Check PSR-12 violations on src and tests
vendor/bin/phpcs src tests --standard=phpcs.xml.dist

# From root project
docker compose exec php vendor/bin/phpcs PrismBundle/src PrismBundle/tests --standard=PrismBundle/phpcs.xml.dist

# Automatically fix violations
vendor/bin/phpcbf src tests --standard=phpcs.xml.dist

# From root project
docker compose exec php vendor/bin/phpcbf PrismBundle/src PrismBundle/tests --standard=PrismBundle/phpcs.xml.dist

# Detailed report with summary
vendor/bin/phpcs src tests --standard=phpcs.xml.dist --report=summary
```

---

### 🔍 Static Analysis (PHPStan)

```bash
# Analyze source code (level 9 maximum)
vendor/bin/phpstan analyse src -c phpstan.neon --level=9 --memory-limit=256M

# Analyze tests (level 9 maximum)
vendor/bin/phpstan analyse tests -c phpstan.neon --level=9 --memory-limit=256M

# From root project - analyze src
docker compose exec php vendor/bin/phpstan analyse PrismBundle/src -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M

# From root project - analyze tests
docker compose exec php vendor/bin/phpstan analyse PrismBundle/tests -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M
```

> **⚠️ Important note**: Test analysis requires `--memory-limit=256M` because PHPStan must scan all fake classes. Source code analysis may also need it depending on complexity.

---

### 📊 Code Coverage

**From the bundle directory** (without Docker):

```bash
# With PCOV (recommended - faster)
php -d pcov.enabled=1 vendor/bin/phpunit -c phpunit.xml.dist --coverage-html var/report
php -d pcov.enabled=1 vendor/bin/phpunit -c phpunit.xml.dist --coverage-text

# With Xdebug (slower)
XDEBUG_MODE=coverage vendor/bin/phpunit -c phpunit.xml.dist --coverage-html var/report
XDEBUG_MODE=coverage vendor/bin/phpunit -c phpunit.xml.dist --coverage-text

# Open HTML report
open var/report/index.html  # macOS
xdg-open var/report/index.html  # Linux
```

**From the root project with Docker** (⭐ **RECOMMENDED**):

```bash
# Generate HTML report with PCOV
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-html PrismBundle/var/report

# Generate text report in terminal
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-text

# Open HTML report
xdg-open PrismBundle/var/report/index.html  # Linux
open PrismBundle/var/report/index.html  # macOS
```

> **💡 PCOV Tip**: The `-d pcov.directory=/var/www/html/PrismBundle` option is **essential** in Docker for PCOV to scan the correct directory. Without it, coverage will be 0%.

---

### 🎯 Quality Standards

- ✅ **399 unit tests** (952 assertions, 0 mocks)
- ✅ **Coverage** 100% Classes (17/17), 100% Methods (133/133), 100% Lines (923/923)
- ✅ **PHPStan level 9** 0 error on 65 files (38 src + 27 tests)
- ✅ **PSR-12 PHPCS** 0 violation on 65 files (src + tests)
- ✅ **PHP 8.3** strict typing
- ✅ **Architecture** Pure hexagonal (Domain/Application/Infrastructure)

See [Complete tests](docs/en/PRISM.md#-tests-and-quality) for commands.

## 📄 License

MIT - See `LICENSE` file.

## 👤 Author

**David Lhoumaud**
- Email: dlhoumaud@gmail.com

See [prism-office](https://github.com/dlhoumaud/prism-office) for GUI.
