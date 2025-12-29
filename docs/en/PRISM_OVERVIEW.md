# 🎯 Functional Scenarios System - Overview

## 📦 Introduction

The **Functional Scenarios System** (Prism) is an advanced fixtures framework designed to generate reproducible and isolated test data.

### Key Features

- **Isolation scopes**: Each developer can work on their own dataset
- **Multi-database**: Native support with cross-DB lookups
- **Complete traceability**: All created resources are tracked
- **Smart purge**: Automatic deletion respecting FK constraints
- **3 approaches available**: Pure YAML, Pure PHP, or Hybrid
- **44 faker data types**: French + international
- **Complete tests**: 78 unit tests, 176 assertions

### Provided example scenarios

- `test_users` - Simple example (2 users)
- `chat_conversation` - Example with relations (3 users + messages)
- `advanced_example` - Complete template with all features
- `hybrid_example` - Hybrid example (YAML + PHP)

---

## 🗄️ Database

### Tracking table

```sql
CREATE TABLE prism_resource (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    prism_name VARCHAR(100) NOT NULL,    -- Scenario name
    scope VARCHAR(50) NOT NULL,             -- Isolation scope
    table_name VARCHAR(64) NOT NULL,        -- Resource table
    row_id BIGINT NOT NULL,                 -- Created row ID
    created_at DATETIME NOT NULL,           -- Creation date
    
    INDEX idx_prism_scope (prism_name, scope),
    INDEX idx_scope (scope),
    INDEX idx_table_row (table_name, row_id),
    INDEX idx_created_at (created_at)
);
```

---

## 🎮 Available CLI commands

### 1️⃣ List scenarios

```bash
php bin/console app:prism:list
```

**Output:**
```
Available functional scenarios
==================================

 ------------------- --------------------------------------------------------------------- 
  Scenario name       Class                                                               
 ------------------- --------------------------------------------------------------------- 
  advanced_example    Prism\Infrastructure\Prism\AdvancedExamplePrism   
  chat_conversation   Prism\Infrastructure\Prism\ChatConversationPrism  
  test_users          Prism\Infrastructure\Prism\TestUsersPrism         
 ------------------- --------------------------------------------------------------------- 
```

### 2️⃣ Load a scenario

```bash
# Syntax
php bin/console app:prism:load <prism_name> --scope=<scope>

# Examples
php bin/console app:prism:load test_users --scope=dev_alice
php bin/console app:prism:load chat_conversation --scope=test_qa
php bin/console app:prism:load advanced_example --scope=dev_bob
```

**What it does:**
1. ✅ Automatic purge of existing data from the same scope
2. ✅ Automatic transaction (rollback on error)
3. ✅ Tracking of all created resources
4. ✅ Detailed logging
5. ✅ Execution report

### 3️⃣ Purge a scenario

```bash
# Purge a specific scenario
php bin/console app:prism:purge test_users --scope=dev_alice

# Purge all scenarios from a scope
php bin/console app:prism:purge --scope=dev_alice --all
```

**What it does:**
1. ✅ Reads tracked resources
2. ✅ Deletes in reverse order (respects FK)
3. ✅ Cleans traces in prism_resource
4. ✅ Automatic transaction

---

## 📝 3 Example scenarios provided

### 🟢 `test_users` - Simple

**Creates:**
- 2 users (admin + user)

**Usage:**
```bash
php bin/console app:prism:load test_users --scope=dev_test
```

**Created data:**
- `admin_dev_test@example.test`
- `user_dev_test@example.test`

---

### 🟡 `chat_conversation` - Relations

**Creates:**
- 3 users (Alice, Bob, Charlie)
- 5 chat messages between them (with FK)

**Usage:**
```bash
php bin/console app:prism:load chat_conversation --scope=dev_test
```

**Demonstrates:**
- Relations between tables (sender_id, receiver_id)
- Automatic purge in reverse order

---

### 🔴 `advanced_example` - Complete template

**Creates:**
- 5 users (admin, manager, 3 users)
- 7 messages between them
- ACL configuration
- Statistics

**Usage:**
```bash
php bin/console app:prism:load advanced_example --scope=dev_demo
```

**Demonstrates:**
- Organization in steps
- Structured logging
- Statistics
- Error handling
- Template for your scenarios

---

## 🎨 How to create your scenario

### Minimal template

```php
<?php

declare(strict_types=1);

namespace App\Prism;

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
        $scopeStr = $scope->toString();
        
        // Your logic here
        $id = $this->insertAndTrack('my_table', [
            'field' => sprintf('value_%s', $scopeStr),
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Resource created: {id}', ['id' => $id]);
    }
}
```

### Available methods in `AbstractPrism`

```php
// Get DBAL connection
$connection = $this->getConnection();

// Insert AND track automatically (⭐ recommended)
$id = $this->insertAndTrack('table', $data, $types);

// Track manually
$this->trackResource('table', $rowId);

// Logger (PSR-3)
$this->logger->info('Message', ['context' => 'value']);
$this->logger->debug('Debug');
$this->logger->error('Error');

// The purge() method is ALREADY implemented ✅
// No need to write it unless special logic
```

---

## 🏗️ Hexagonal architecture respected

### ✅ Respected principles

1. **Domain depends on nothing** ✅
   - No Symfony import
   - No Doctrine import
   - Only interfaces, entities, value objects

2. **Dependency inversion** ✅
   - Interfaces defined in the bundle
   - Implementations provided by the bundle
   - Extensible by the user

3. **Simple application** ✅
   - Pure Use Cases
   - Operation coordination
   - Transaction management

4. **Adaptable infrastructure** ✅
   - Doctrine DBAL today
   - Can be replaced by something else tomorrow
   - Business code remains unchanged

---

## 📊 Complete workflow

### Loading a scenario

```
1. CLI: php bin/console app:prism:load test_users --scope=dev_alice
              ↓
2. Command processes the request
              ↓
3. System automatically purges existing scope
   - DELETE old resources + tracking
              ↓
4. Scenario execution
   - INSERT INTO users (...)
   - INSERT INTO prism_resource (...) for each resource
              ↓
5. COMMIT transaction
              ↓
6. Success! Data is created and tracked
```

### Purging a scenario

```
1. CLI: php bin/console app:prism:purge test_users --scope=dev_alice
              ↓
2. Command processes the request
              ↓
3. System retrieves tracked resources
   - SELECT * FROM prism_resource WHERE prism_name=... AND scope=...
   - For each resource (in REVERSE order):
     - DELETE FROM <table> WHERE id = <row_id>
   - DELETE FROM prism_resource WHERE prism_name=... AND scope=...
              ↓
4. Success! All data is cleanly deleted
```

---

## 🎓 Key points to remember

### ✅ Advantages

1. **Reliability**
   - Pivot table = guaranteed traceability
   - Automatic purge in reverse order
   - Automatic transactions

2. **Developer Experience**
   - Simple API (`insertAndTrack()`)
   - Auto-discovery of scenarios
   - User-friendly CLI
   - Integrated logging
   - Maintainability

4. **Isolation**
   - Personal scopes
   - No collision between developers
   - Selective purge

### 🎯 Best practices

- ✅ Use prefixed scopes (`dev_`, `test_`, `qa_`)
- ✅ Always purge before loading (automatic)
- ✅ Use `insertAndTrack()` instead of `insert()` + `track()`
- ✅ Log your important operations
- ✅ Create parents before children (FK)
- ✅ Regularly purge your test scopes

### ⚠️ Avoid

- ❌ Don't use production scopes
- ❌ Don't forget to track resources
- ❌ Don't create children before parents (FK)
- ❌ Don't purge another developer's scope

---

## 🚀 Getting started

### 1. Run the migration

```bash
php bin/migration-scripts/migrate.php
```

### 2. Test a scenario

```bash
php bin/console app:prism:load test_users --scope=dev_test
```

### 3. Check in DB

```sql
SELECT * FROM users WHERE email LIKE '%dev_test%';
SELECT * FROM prism_resource WHERE scope = 'dev_test';
```

### 4. Purge

```bash
php bin/console app:prism:purge test_users --scope=dev_test
```

---

## 📚 Complete documentation

- **Complete guide**: `docs/PRISM.md`
- **Installation**: `PRISM_INSTALLATION.md`

---

## 🎉 You're ready!

The functional scenarios system is **100% functional** and **production-ready**.

**Next steps:**
1. ✅ Run the migration
2. ✅ Test example scenarios
3. ✅ Create your own scenarios
4. ✅ Integrate into your test workflows

**Enjoy!** 🚀
