# Installation and Configuration

> **Note**: This guide details the installation **after** adding the bundle via Composer.  
> For initial installation, see the [main README](../README.md#-installation).

## 🚀 Post-Installation

### 1. Run the migration

```bash
php bin/migration-scripts/migrate.php
```

Creates the `prism_resource` table for tracking.

### 2. Verify installation

```bash
php bin/console app:prism:list
```

Should display the example scenarios.

### 3. Quick test

```bash
# Load
php bin/console app:prism:load test_users --scope=dev_test

# Verify in SQL
# SELECT * FROM users WHERE email LIKE '%dev_test%';

# Purge
php bin/console app:prism:purge test_users --scope=dev_test
```

---

## 🛠️ Create your first scenario

Create `prism/scripts/MyPrismPrism.php`:

```php
<?php
namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
use Prism\Domain\ValueObject\{Scope, PrismName};

final class MyPrismPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('my_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->insertAndTrack('users', [
            'username' => "user_{$scope}",
            'email' => "user_{$scope}@test.com",
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
    }
}
```

Test:

```bash
php bin/console cache:clear
php bin/console app:prism:load my_prism --scope=test
```

For more examples, see [PHP Guide](PRISM_PHP.md) or [YAML Guide](PRISM_YAML.md).

---

## 🔍 Debugging

### View tracked resources

```sql
-- All resources from a scope
SELECT * FROM prism_resource WHERE scope = 'dev_alice';

-- Resources from a specific scenario
SELECT * FROM prism_resource 
WHERE prism_name = 'test_users' AND scope = 'dev_alice';
```

### Verbose logs

```bash
php bin/console app:prism:load test_users --scope=test -vvv
```

### Cache

If your changes are not taken into account:

```bash
php bin/console cache:clear
```

---

## 🎓 Going further

Consult the complete documentation: `docs/PRISM.md`

### Advanced topics:
- Managing complex relations (FK)
- Naming conventions
- Scope best practices
- Scenarios with transactions
- Testing scenarios

---

## ✨ Benefits of this implementation

### 1. Pivot table (complete traceability)
✅ Maximum purge reliability  
✅ Complete audit of created resources  
✅ Support for complex FK relations  
✅ Easy debugging  

---

## 🐛 Common issues

**"Table prism_resource doesn't exist"**  
→ `php bin/migration-scripts/migrate.php`

**"Scenario not found"**  
→ Check that the file ends with `Prism.php`  
→ `php bin/console cache:clear`

**"Foreign key constraint fails"**  
→ Create parents before children

---

## 📚 Next steps

- [Quick start guide](PRISM_QUICKSTART.md)
- [Complete documentation](PRISM.md)
- [YAML scenarios](PRISM_YAML.md)
- [PHP scenarios](PRISM_PHP.md)
