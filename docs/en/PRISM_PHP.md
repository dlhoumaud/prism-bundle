# PHP Scenarios - Complete Guide

## 📋 Overview

PHP scenarios allow you to create **test contexts with complex business logic**. Use this approach when you need:

✅ **Dynamic calculations** - Prices, discounts, statistics  
✅ **Loops and conditions** - Complex conditional logic  
✅ **Workflows** - State transitions, business processes  
✅ **Full control** - Complete access to PHP API  

---

## 🚀 Quick Start

### Basic structure

In your application, create a file in `prism/scripts/`:

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Domain\Contract\DatabaseNameResolverInterface;
use Prism\Domain\Contract\FakeDataGeneratorInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
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
        
        // Your logic here...
        $this->createUsers($scopeStr);
    }
    
    private function createUsers(string $scope): void
    {
        // Implementation...
    }
}
```

### Load the scenario

```bash
php bin/console app:prism:load my_prism --scope=dev
```

---

## 🛠️ Available API

### `insertAndTrack()` - Insertion with automatic tracking

```php
// Simple insertion with automatic tracking on 'id' column
$userId = $this->insertAndTrack('users', [
    'username' => sprintf('user_%s', $scope),
    'email' => sprintf('user_%s@example.test', $scope),
    'password' => password_hash('secret', PASSWORD_BCRYPT),
], []);

// Insertion with Doctrine types
$postId = $this->insertAndTrack('posts', [
    'title' => 'My article',
    'author_id' => $userId,
    'published_at' => new \DateTimeImmutable(),
], [
    'published_at' => 'datetime_immutable'
]);

// Tracking on custom column (e.g., for tables with VARCHAR ID)
$messageId = $this->insertAndTrack('chat_messages', [
    'id' => sprintf('msg_%s_%s', $scope, uniqid()),
    'user_id' => $userId,
    'message' => 'Hello',
], [], 'user_id'); // Track by user_id instead of id

// Insertion in secondary database
$logId = $this->insertAndTrack('audit_logs', [
    'user_id' => $userId,
    'action' => 'user_created',
    'created_at' => new \DateTimeImmutable(),
], [
    'created_at' => 'datetime_immutable'
], 'id', 'hexagonal_secondary');
```

### `getRepository()` - Direct repository access

```php
// Manual insertion (returns int|string|null)
$id = $this->getRepository()->insert('users', [
    'username' => 'john',
    'email' => 'john@test.com'
], []);

// Then manual tracking if needed
$this->trackResource('users', $id);

// SELECT queries
$results = $this->getRepository()->executeQuery(
    'SELECT * FROM users WHERE username = ?',
    ['john']
);

// UPDATE/DELETE queries
$affectedRows = $this->getRepository()->executeStatement(
    'UPDATE users SET active = ? WHERE id = ?',
    [true, $userId]
);

// Deletion with conditions
$deleted = $this->getRepository()->delete('users', [
    'username' => 'john'
]);
```

#### `trackResource()` - Manual tracking

**Full syntax**:
```php
protected function trackResource(
    string $tableName,
    int|string $rowId,
    string $idColumnName = 'id',
    ?string $dbName = null
): void
```

**Usage**:

```php
// Standard tracking ('id' column)
$this->trackResource('users', $userId);

// Tracking with custom pivot (alternative column)
$this->trackResource('chat_messages', $userId, 'user_id');
$this->trackResource('orders', $customerId, 'customer_id');
$this->trackResource('logs', $sessionId, 'session_id');

// Tracking in secondary database
$this->trackResource('audit_logs', $userId, 'id', 'hexagonal_secondary');
```

**Use case**: When you insert manually with `getRepository()->insert()` instead of `insertAndTrack()`.

**Example with manual insertion**:

```php
public function load(Scope $scope): void
{
    $scopeStr = $scope->toString();
    
    // Create a user
    $userId = $this->insertAndTrack('users', [
        'username' => sprintf('alice_%s', $scopeStr),
        'email' => sprintf('alice_%s@test.com', $scopeStr),
        'password' => password_hash('secret', PASSWORD_BCRYPT),
    ], []);
    
    // Manual insertion of a message
    $messageId = $this->getRepository()->insert('chat_messages', [
        'id' => sprintf('msg_%s_%s', $scopeStr, uniqid()),
        'user_id' => $userId,
        'message' => 'Hello',
        'created_at' => new \DateTimeImmutable(),
    ], [
        'created_at' => 'datetime_immutable'
    ]);
    
    // Manual tracking with custom pivot
    $this->trackResource('chat_messages', $userId, 'user_id');
    // ☝️ Track by user_id instead of UUID message_id
}
```

**Why manual tracking?**

- ✅ Insertion with `executeStatement()` (custom SQL queries)
- ✅ Need the ID before tracking
- ✅ Complex conditional logic
- ✅ Batch inserts

**Comparison**:

```php
// ❌ Less convenient: insertion + manual tracking
$id = $this->getRepository()->insert('users', $data, []);
$this->trackResource('users', $id);

// ✅ Simpler: insertAndTrack does both
$id = $this->insertAndTrack('users', $data, []);

// ✅ With pivot: insertAndTrack handles everything
$id = $this->insertAndTrack('chat_messages', $data, [], 'user_id');

// ✅ But sometimes necessary for custom SQL
$this->getRepository()->executeStatement(
    'INSERT INTO logs (user_id, action) SELECT id, "created" FROM users WHERE scope = ?',
    [$scope]
);
// Then manual tracking by user_id
$this->trackResource('logs', $userId, 'user_id');
```

---

## 🔑 Custom Pivot - Alternative tracking

### What is custom pivot?

By default, the system tracks resources by their `id` column. The **custom pivot** allows tracking by another **column**, useful for:

✅ **Tables with VARCHAR ID** - Track by INT FK rather than UUID  
✅ **Multiple relationships** - Track by owning user rather than resource ID  
✅ **Grouped purge** - Delete all resources of a user at once  

### Typical use case: Messages with UUID

**Problem**: `chat_messages` table with VARCHAR `id` (UUID) but you want to delete all messages of a user.

```php
// ❌ Without pivot: track by UUID
$messageId = $this->insertAndTrack('chat_messages', [
    'id' => 'msg_' . uniqid(),
    'user_id' => 42,
    'message' => 'Hello'
], []);
// Track: id = 'msg_123abc' → Purge message by message

// ✅ With pivot: track by user_id
$messageId = $this->insertAndTrack('chat_messages', [
    'id' => 'msg_' . uniqid(),
    'user_id' => 42,
    'message' => 'Hello'
], [], 'user_id');
// Track: user_id = 42 → Purge all user's messages
```

### Syntax with `insertAndTrack()`

```php
public function insertAndTrack(
    string $table,
    array $data,
    array $types = [],
    string $idColumnName = 'id'  // ← Tracking column
): int|string
```

**4th parameter**: Name of the column to track (default: `'id'`)

### Complete example: Chat messages

```php
final class ChatPrism extends AbstractPrism
{
    public function load(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Create a user
        $userId = $this->insertAndTrack('users', [
            'username' => sprintf('alice_%s', $scopeStr),
            'email' => sprintf('alice_%s@test.com', $scopeStr),
            'password' => password_hash('secret', PASSWORD_BCRYPT),
        ], []);
        
        // Create multiple messages tracked by user_id
        for ($i = 1; $i <= 5; $i++) {
            $this->insertAndTrack('chat_messages', [
                'id' => sprintf('msg_%s_%s_%d', $scopeStr, uniqid(), $i),
                'user_id' => $userId,
                'username' => sprintf('alice_%s', $scopeStr),
                'message' => sprintf('Message %d', $i),
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ], 'user_id'); // ← Track by user_id instead of id
        }
        
        $this->logger->info('Messages created with custom pivot', [
            'user_id' => $userId,
            'count' => 5
        ]);
    }
}
```

**On purge**: Automatically deletes all messages where `user_id = $userId`

### Example with dynamic lookup

```php
private function createMessagesWithPivot(string $scope): void
{
    // Retrieve existing user
    $users = $this->getRepository()->executeQuery(
        'SELECT id FROM users WHERE username = ?',
        [sprintf('alice_%s', $scope)]
    );
    
    if (empty($users)) {
        return;
    }
    
    $userId = $users[0]['id'];
    
    // Messages tracked by user_id
    $messages = [
        'Hello everyone!',
        'How are you?',
        'I am available to chat.',
    ];
    
    foreach ($messages as $content) {
        $this->insertAndTrack('chat_messages', [
            'id' => sprintf('msg_%s_%s', $scope, uniqid()),
            'user_id' => $userId,
            'username' => sprintf('alice_%s', $scope),
            'message' => $content,
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ], 'user_id'); // Custom pivot
    }
}
```

### Comparison standard tracking vs pivot

| Aspect | Standard tracking (`id`) | Custom pivot (`user_id`) |
|--------|--------------------------|-------------------------|
| **Tracked column** | `id` (VARCHAR UUID) | `user_id` (INT FK) |
| **Purge** | Message by message | All messages of a user |
| **Performance** | Slower (1 DELETE per message) | Fast (1 DELETE with WHERE) |
| **Use case** | Classic tables with INT ID | Tables with UUID + important FK |

### When to use custom pivot?

✅ **Use custom pivot when**:  
- Your table has VARCHAR `id` (UUID)
- You want to purge by relationship (all messages of a user)
- You have multiple resources linked to a main entity

❌ **Don't use custom pivot when**:  
- Your table has standard INT auto-increment `id`
- Each resource must be purged individually
- No logical FK to group by

### Custom pivot with manual tracking

If you use `getRepository()->insert()` instead of `insertAndTrack()`, you can manually track with pivot:

```php
private function createMessagesBatch(int $userId, string $scope): void
{
    $messages = [
        'Hello!',
        'How are you?',
        'See you soon!',
    ];
    
    foreach ($messages as $content) {
        // Manual insertion
        $messageId = $this->getRepository()->insert('chat_messages', [
            'id' => sprintf('msg_%s_%s', $scope, uniqid()),
            'user_id' => $userId,
            'message' => $content,
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        // Manual tracking with custom pivot
        // ☝️ Don't track by messageId (VARCHAR), but by userId (INT)
        $this->trackResource('chat_messages', $userId, 'user_id');
    }
    
    $this->logger->info('Batch messages created with pivot', [
        'user_id' => $userId,
        'count' => count($messages)
    ]);
}
```

**Result**: All 3 messages are tracked with `user_id`, so purge will delete all messages of this user in one query.

### Available Repository methods

| Method | Description | Return |
|---------|-------------|--------|
| `insert($table, $data, $types)` | Insert into table | `int\|string\|null` |
| `executeQuery($sql, $params)` | SELECT query | `array` |
| `executeStatement($sql, $params)` | UPDATE/DELETE | `int` (affected rows) |
| `delete($table, $criteria)` | Delete with criteria | `int` (deleted rows) |

---

## 🎲 Random data generation

### `fake(type, ...params)` - Helper for fake data

Generates random data for tests.

```php
$this->fake('user')                // Username
$this->fake('email', 'acme.com')   // Email with domain
$this->fake('iban', 'DE')          // German IBAN
$this->fake('date', 'd/m/Y')       // Formatted date
```

**📚 44 available types**: See [Complete Faker Guide](PRISM_FAKER.md)

---

## 📚 Complete Examples

### Example 1: Scenario with pricing calculations

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scenario with complex pricing calculations
 */
final class PricingPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('pricing');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Create a customer
        $clientId = $this->createClient($scopeStr);
        
        // Create products with calculated prices
        $products = $this->createProducts($scopeStr);
        
        // Create an order with progressive discounts
        $this->createOrder($clientId, $products, $scopeStr);
    }
    
    private function createClient(string $scope): int
    {
        return $this->insertAndTrack('clients', [
            'name' => sprintf('Client_%s', $scope),
            'email' => sprintf('client_%s@test.com', $scope),
            'discount_rate' => 0.15, // 15% discount
        ], []);
    }
    
    private function createProducts(string $scope): array
    {
        $products = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $basePrice = 100 * $i;
            $tax = $basePrice * 0.20; // 20% VAT
            $priceIncTax = $basePrice + $tax;
            
            $productId = $this->insertAndTrack('products', [
                'reference' => sprintf('PROD_%s_%03d', $scope, $i),
                'name' => sprintf('Product %d - %s', $i, $scope),
                'price_excl_tax' => $basePrice,
                'price_inc_tax' => $priceIncTax,
                'vat_rate' => 0.20,
            ], []);
            
            $products[] = [
                'id' => $productId,
                'price_excl_tax' => $basePrice,
                'price_inc_tax' => $priceIncTax,
            ];
        }
        
        return $products;
    }
    
    private function createOrder(int $clientId, array $products, string $scope): void
    {
        // Get client's discount rate
        $client = $this->getRepository()->executeQuery(
            'SELECT discount_rate FROM clients WHERE id = ?',
            [$clientId]
        );
        $discountRate = $client[0]['discount_rate'];
        
        // Calculate total with progressive discount
        $totalExclTax = 0;
        $totalIncTax = 0;
        
        foreach ($products as $product) {
            // Progressive discount: +5% per product
            $additionalDiscount = count($products) * 0.05;
            $totalDiscount = min($discountRate + $additionalDiscount, 0.50); // Max 50%
            
            $finalPriceExclTax = $product['price_excl_tax'] * (1 - $totalDiscount);
            $finalPriceIncTax = $product['price_inc_tax'] * (1 - $totalDiscount);
            
            $totalExclTax += $finalPriceExclTax;
            $totalIncTax += $finalPriceIncTax;
        }
        
        $orderId = $this->insertAndTrack('orders', [
            'reference' => sprintf('ORD_%s_%s', $scope, date('YmdHis')),
            'client_id' => $clientId,
            'total_excl_tax' => $totalExclTax,
            'total_inc_tax' => $totalIncTax,
            'applied_discount_rate' => $discountRate,
            'status' => 'pending',
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Order created with complex calculations', [
            'order_id' => $orderId,
            'total_inc_tax' => $totalIncTax,
            'nb_products' => count($products)
        ]);
    }
}
```

### Example 2: Workflow with state transitions

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scenario simulating a workflow with states
 */
final class ProjectWorkflowPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('project_workflow');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Create a user
        $userId = $this->createUser($scopeStr);
        
        // Create a project
        $projectId = $this->createProject($userId, $scopeStr);
        
        // Simulate workflow
        $this->simulateTransitions($projectId, $scopeStr);
    }
    
    private function createUser(string $scope): int
    {
        return $this->insertAndTrack('users', [
            'username' => sprintf('project_manager_%s', $scope),
            'email' => sprintf('manager_%s@test.com', $scope),
            'password' => password_hash('secret', PASSWORD_BCRYPT),
        ], []);
    }
    
    private function createProject(int $userId, string $scope): int
    {
        return $this->insertAndTrack('projects', [
            'name' => sprintf('Project_%s', $scope),
            'manager_id' => $userId,
            'state' => 'draft',
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
    }
    
    private function simulateTransitions(int $projectId, string $scope): void
    {
        $states = ['draft', 'review', 'approved', 'published'];
        $startDate = new \DateTimeImmutable('-4 days');
        
        foreach ($states as $index => $state) {
            // Calculate transition date
            $transitionDate = $startDate->modify(sprintf('+%d days', $index));
            
            // Create transition history
            $this->insertAndTrack('project_history', [
                'project_id' => $projectId,
                'state' => $state,
                'comment' => sprintf('Transition to %s', $state),
                'created_at' => $transitionDate,
            ], [
                'created_at' => 'datetime_immutable'
            ]);
            
            $this->logger->debug('Transition recorded', [
                'state' => $state,
                'date' => $transitionDate->format('Y-m-d H:i:s')
            ]);
        }
        
        // Update project final state
        $this->getRepository()->executeStatement(
            'UPDATE projects SET state = ?, updated_at = ? WHERE id = ?',
            ['published', new \DateTimeImmutable(), $projectId]
        );
        
        $this->logger->info('Workflow completed', ['project_id' => $projectId]);
    }
}
```

### Example 3: Statistics and aggregations

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scenario generating user statistics
 */
final class StatisticsPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('statistics');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Create users
        $userIds = $this->createUsers($scopeStr);
        
        // Create actions for each user
        foreach ($userIds as $userId) {
            $this->createActions($userId, $scopeStr);
        }
        
        // Calculate and persist statistics
        foreach ($userIds as $userId) {
            $this->calculateStatistics($userId, $scopeStr);
        }
    }
    
    private function createUsers(string $scope): array
    {
        $userIds = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $userIds[] = $this->insertAndTrack('users', [
                'username' => sprintf('user_%d_%s', $i, $scope),
                'email' => sprintf('user_%d_%s@test.com', $i, $scope),
                'password' => password_hash('secret', PASSWORD_BCRYPT),
            ], []);
        }
        
        return $userIds;
    }
    
    private function createActions(int $userId, string $scope): void
    {
        $nbActions = rand(5, 20);
        
        for ($i = 0; $i < $nbActions; $i++) {
            $this->insertAndTrack('user_actions', [
                'user_id' => $userId,
                'type' => ['view', 'click', 'download'][rand(0, 2)],
                'created_at' => new \DateTimeImmutable(sprintf('-%d hours', rand(1, 168))),
            ], [
                'created_at' => 'datetime_immutable'
            ]);
        }
    }
    
    private function calculateStatistics(int $userId, string $scope): void
    {
        // Retrieve user actions
        $actions = $this->getRepository()->executeQuery(
            'SELECT type, COUNT(*) as count FROM user_actions WHERE user_id = ? GROUP BY type',
            [$userId]
        );
        
        $stats = [
            'views' => 0,
            'clicks' => 0,
            'downloads' => 0,
        ];
        
        foreach ($actions as $action) {
            $stats[$action['type'] . 's'] = (int) $action['count'];
        }
        
        // Calculate activity score
        $score = ($stats['views'] * 1) + ($stats['clicks'] * 2) + ($stats['downloads'] * 5);
        
        // Persist statistics
        $this->insertAndTrack('user_statistics', [
            'user_id' => $userId,
            'total_views' => $stats['views'],
            'total_clicks' => $stats['clicks'],
            'total_downloads' => $stats['downloads'],
            'activity_score' => $score,
            'calculated_at' => new \DateTimeImmutable(),
        ], [
            'calculated_at' => 'datetime_immutable'
        ]);
        
        $this->logger->debug('Statistics calculated', [
            'user_id' => $userId,
            'score' => $score
        ]);
    }
}
```

---

## 📋 Best Practices

### 1. Scenario naming

```php
// ✅ Good: descriptive and consistent
PrismName::fromString('users_with_acl');
PrismName::fromString('chat_conversation');
PrismName::fromString('product_pricing');

// ❌ Bad: too vague
PrismName::fromString('test');
PrismName::fromString('data');
```

### 2. Use insertAndTrack() by default

```php
// ✅ Good: automatic tracking
$userId = $this->insertAndTrack('users', $data, []);

// ❌ Bad: unnecessary manual tracking
$id = $this->getRepository()->insert('users', $data, []);
$this->trackResource('users', $id);
```

### 3. Log important steps

```php
public function load(Scope $scope): void
{
    $this->logger->info('Starting Pricing scenario');
    
    $clientId = $this->createClient($scope->toString());
    $this->logger->info('Client created', ['id' => $clientId]);
    
    $this->createProducts($scope->toString());
    $this->logger->info('Products created');
    
    $this->logger->info('✓ Pricing scenario loaded');
}
```

### 4. Organize code in private methods

```php
public function load(Scope $scope): void
{
    $scopeStr = $scope->toString();
    
    // ✅ Readable and modular
    $userId = $this->createUser($scopeStr);
    $projectId = $this->createProject($userId, $scopeStr);
    $this->addTasks($projectId, $scopeStr);
}

private function createUser(string $scope): int { /* ... */ }
private function createProject(int $userId, string $scope): int { /* ... */ }
private function addTasks(int $projectId, string $scope): void { /* ... */ }
```

### 5. Handle Doctrine types correctly

```php
// ✅ Good: explicit types
$this->insertAndTrack('events', [
    'title' => 'Meeting',
    'start_date' => new \DateTimeImmutable('2025-01-15 10:00:00'),
    'is_active' => true,
    'priority' => 5,
], [
    'start_date' => 'datetime_immutable',
    'is_active' => 'boolean',
    'priority' => 'integer',
]);

// ❌ Bad: missing types
$this->insertAndTrack('events', [
    'start_date' => '2025-01-15 10:00:00', // ⚠️ String instead of DateTimeImmutable
    'is_active' => 1, // ⚠️ Int instead of bool
], []);
```

### 6. Automatic purge: don't override unnecessarily

```php
// ✅ Good: let automatic purge handle it
// AbstractPrism::purge() handles reverse order automatically

// ⚠️ Special case only: custom purge
public function purge(Scope $scope): void
{
    // Custom logic BEFORE auto purge
    $this->deleteExportedFiles($scope);
    
    // Then automatic purge (tracking + reverse order)
    parent::purge($scope);
}
```

---

## 🔗 Resources

- **[Main Documentation](PRISM.md)** - System overview
- **[YAML Guide](PRISM_YAML.md)** - Create YAML scenarios
- **[Hybrid Guide](PRISM_HYBRID.md)** - Combine YAML + PHP
