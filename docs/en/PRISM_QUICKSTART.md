# ⚡ Quick Start - Functional Scenarios

## 🚀 Installation (2 minutes)

```bash
# 1. Run the migration
php bin/migration-scripts/migrate.php

# 2. Verify installation
php bin/console app:prism:list
```

---

## 🎮 Usage

### Load a scenario
```bash
php bin/console app:prism:load test_users --scope=dev_<your_name>
```

### Purge a scenario
```bash
php bin/console app:prism:purge test_users --scope=dev_<your_name>
```

### List scenarios
```bash
php bin/console app:prism:list
```

---

## 📝 Create your scenario (1 minute)

**File:** `App\Prism\MyPrismPrism.php`

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
        // Insert and track automatically
        $id = $this->insertAndTrack('my_table', [
            'field' => sprintf('value_%s', $scope->toString()),
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Created: {id}', ['id' => $id]);
    }
    
    // purge() is already implemented automatically ✅
}
```

**Test:**
```bash
php bin/console cache:clear
php bin/console app:prism:load my_prism --scope=test
```

---

## 📚 Documentation

- **Complete guide**: [`docs/PRISM.md`](docs/PRISM.md)
- **Installation**: [`PRISM_INSTALLATION.md`](PRISM_INSTALLATION.md)
- **Overview**: [`PRISM_OVERVIEW.md`](PRISM_OVERVIEW.md)

---

## 🎯 3 Example scenarios provided

| Scenario | Description | Complexity |
|----------|-------------|------------|
| `test_users` | 2 simple users | 🟢 Simple |
| `chat_conversation` | 3 users + 5 messages | 🟡 Medium |
| `advanced_example` | 5 users + messages + ACL | 🔴 Advanced |

---

## ✨ Benefits

✅ **Isolated** - Personal scopes, no collision  
✅ **Reliable** - Pivot table, automatic purge  
✅ **Simple** - `insertAndTrack()` API, auto-discovery  
✅ **Testable** - Clean and modular architecture  

---

## 🎉 You're ready!

**Start now:**

```bash
# 1. Migration
php bin/migration-scripts/migrate.php

# 2. Test
php bin/console app:prism:load test_users --scope=dev_test

# 3. Check DB
# SELECT * FROM users WHERE email LIKE '%dev_test%';

# 4. Purge
php bin/console app:prism:purge test_users --scope=dev_test
```

**Enjoy!** 🚀
