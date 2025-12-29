# Functional Scenarios System

## 📋 Overview

The functional scenarios system allows you to create **complete and reproducible business contexts** for functional tests. Each scenario generates controlled database data and can be cleanly purged.

### Benefits

✅ **Deterministic** - Data is always created the same way  
✅ **Isolated** - Each developer can have their scope without collision  
✅ **Traceable** - All created resources are tracked in a pivot table  
✅ **Reversible** - Automatic purge respecting FK constraints  
✅ **Hexagonal Architecture** - Respect of Domain/Application/Infrastructure principles  
✅ **Flexibility** - Choose the approach adapted to your need

### Types of available scenarios

The system supports **3 approaches**:

| Type | Documentation | When to use |
|------|---------------|-------------|
| **[Pure YAML](PRISM_YAML.md)** | Complete YAML Guide | Simple data, lookups, rapid prototyping |
| **[Pure PHP](PRISM_PHP.md)** | Complete PHP Guide | Complex logic, calculations, loops, conditions |
| **[Hybrid](PRISM_HYBRID.md)** | Hybrid Guide | YAML structure + PHP enrichment |

**See also**: [Faker Guide](PRISM_FAKER.md) - 44 random data types (YAML + PHP)

---

## 📖 Essential commands

### List available scenarios

```bash
php bin/console app:prism:list
```

Displays all registered scenarios (PHP, YAML, Hybrid).

### Load a scenario

```bash
# Load with default scope
php bin/console app:prism:load scenario_name

# Load with custom scope
php bin/console app:prism:load scenario_name --scope=my_scope
```

**Note**: Loading automatically purges existing data from the same scope before creating new ones.

### Purge a scenario

```bash
# Purge a specific scenario
php bin/console app:prism:purge scenario_name --scope=my_scope

# Purge all scenarios from a scope
php bin/console app:prism:purge --scope=my_scope --all
```

---

## 🎯 Decision matrix

| Criterion | Pure YAML | Pure PHP | Hybrid |
|-----------|-----------|----------|---------|
| **Logic complexity** | None | High | Medium |
| **Loops/Conditions** | ❌ No | ✅ Yes | ✅ Yes |
| **Dynamic calculations** | ❌ No | ✅ Yes | ✅ Yes |
| **Ease of writing** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **FK Lookups** | ✅ Yes | ✅ Yes | ✅ Yes |
| **Placeholders** | ✅ Yes | ❌ Manual | ✅ Yes |
| **Rapid prototyping** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |

### Use case examples

#### Pure YAML → [Complete guide](PRISM_YAML.md)

- Create test users
- Define roles/permissions (ACL)
- Load reference data
- Simple relations with lookups

#### Pure PHP → [Complete guide](PRISM_PHP.md)

- Calculate prices/discounts
- Generate statistics
- Simulate complex workflows
- Business logic with conditions

#### Hybrid → [Complete guide](PRISM_HYBRID.md)

- Base data (YAML) + enrichment (PHP)
- Simple structure + dynamic calculations
- Progressive prototyping

---

## 🧪 Provided example scenarios

### TestUsersPrism (Pure PHP)

```bash
php bin/console app:prism:load test_users --scope=dev_john
```

**Data:** 1 admin + 1 user

### ChatConversationPrism (Pure PHP)

```bash
php bin/console app:prism:load chat_conversation --scope=dev_alice
```

**Data:** 3 users + 5 messages with FK

### AdvancedExamplePrism (Pure YAML)

```bash
php bin/console app:prism:load advanced_example_yaml --scope=test
```

**See [PRISM_YAML.md](PRISM_YAML.md)**

### HybridExamplePrism (Hybrid)

```bash
php bin/console app:prism:load hybrid_example --scope=demo
```

**Data:** User + messages (YAML) + statistics + notifications (PHP)

---

## 🔍 Scope naming

Use clear prefixes:

- `dev_<name>`: Individual developers (e.g., `dev_alice`)
- `test_<name>`: Automated tests (e.g., `test_integration`)
- `qa_<name>`: QA team (e.g., `qa_team_alpha`)
- `staging`: Staging environment
- `demo`: Client demonstrations

---

## 🔍 Tracking table

The `prism_resource` table:

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Auto-incremented ID |
| `prism_name` | VARCHAR(100) | Scenario name |
| `scope` | VARCHAR(50) | Isolation scope |
| `table_name` | VARCHAR(64) | Table containing the resource |
| `id_column_name` | VARCHAR(64) | Tracked ID column |
| `row_id` | VARCHAR(255) | Created row ID |
| `created_at` | DATETIME | Creation date |

### Useful queries

```sql
-- View all resources from a scope
SELECT * FROM prism_resource 
WHERE scope = 'dev_john' 
ORDER BY created_at DESC;

-- Count by scenario
SELECT prism_name, COUNT(*) as total
FROM prism_resource 
WHERE scope = 'dev_john'
GROUP BY prism_name;
```

---

## 🛠️ Writing a PHP Scenario

### Basic structure

In your application, create a file in `src/Prism/`:

```php
<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Prism;

use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Psr\Log\LoggerInterface;

final class MyPrismPrism extends AbstractPrism
{
    public function __construct(
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($repository, $tracker, $logger);
    }

    public function getName(): PrismName
    {
        return PrismName::fromString('my_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        $this->logger->info('Loading MyPrism scenario', [
            'scope' => $scopeStr
        ]);
        
        // Your logic here...
        $this->createUsers($scopeStr);
        
        $this->logger->info('✓ MyPrism scenario loaded');
    }
    
    private function createUsers(string $scope): void
    {
        // Implementation...
    }
}
```

### Available methods

See [Complete PHP Guide](PRISM_PHP.md) for all available methods.

---

## 🔀 Writing a Hybrid Scenario (YAML + PHP)

Hybrid scenarios combine YAML simplicity for structured data with PHP power for complex logic.

### When to use a hybrid scenario?

✅ **Simple data structure** defined in YAML  
✅ **Complex business logic** added in PHP  
✅ **Dynamic calculations** on base data  
✅ **Enrichment** of YAML data  

See [Complete Hybrid Guide](PRISM_HYBRID.md).

---

## 📖 Usage

See command details in [Overview](PRISM_OVERVIEW.md).

---

## 🧪 Provided example scenarios

See detailed examples in [Overview](PRISM_OVERVIEW.md).

---

## 🔍 Scope naming

See recommendations in [Overview](PRISM_OVERVIEW.md).

---

## ⚙️ Configuration

The `config/services/Prism.yaml` file configures:

- Implementations of Domain ports
- Use case injection
- Auto-discovery of scenarios via tags
- Logging on the `prism` channel

---

## 🐛 Debugging

### View detailed logs

```bash
php bin/console app:prism:load my_prism --scope=test -vvv
```

### Common errors

**"Scenario not found"**
- Check that your class extends `AbstractPrism` (or `YamlPrism` for hybrid)
- Check that the file ends with `Prism.php`
- Clear cache: `php bin/console cache:clear`

**"Foreign key constraint fails" during purge**
- The system automatically purges in reverse order
- If you have ON DELETE CASCADE, check DB constraints

**"Lookup returns no rows"**
- Check that data exists before lookup
- Check `where` conditions in your YAML lookup
- Use `-vvv` to see SQL queries

---

## 📚 Resources

- **[Complete PHP Guide](PRISM_PHP.md)** - Create pure PHP scenarios
- **[Complete YAML Guide](PRISM_YAML.md)** - Create pure YAML scenarios
- **[Hybrid Guide](PRISM_HYBRID.md)** - Create Hybrid scenarios (YAML + PHP)
- **[Hexagonal Architecture](ARCHITECTURE.md)** - System overview
- **[ACL Permissions](PERMISSIONS.md)** - Rights management
- **[Doctrine DBAL Documentation](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/)** - Database API

---

## 📝 License

This system is part of the hexagonal-symfony project under proprietary license.
