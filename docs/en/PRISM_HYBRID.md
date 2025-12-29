# Hybrid Scenarios (YAML + PHP) - Complete Guide

## 📋 Overview

Hybrid scenarios combine **YAML simplicity** for structured data with **PHP power** for complex logic.

### When to use a hybrid scenario?

✅ **Simple data structure** defined in YAML  
✅ **Complex business logic** added in PHP  
✅ **Dynamic calculations** on base data  
✅ **Data enrichment** from YAML  
✅ **Progressive prototyping** - Start with YAML, add PHP as needed

---

## 🚀 Quick Start

### 1. Create the YAML file

Create `prism/yaml/my_prism.yaml`:

```yaml
load:
  # Base user
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@test.com"
      password: "{{ hash('password') }}"

  # Simple message
  - table: messages
    data:
      user_id:
        table: users
        where:
          username: "user_{{ scope }}"
        return: id
      content: "Base message from YAML"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

### 2. Create the PHP class

In your application, create `prism/scripts/MyPrismPrism.php`:

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
use Prism\Application\Prism\YamlPrism;
use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Application\Contract\PrismLoaderInterface;
use Prism\Domain\Contract\DatabaseNameResolverInterface;
use Prism\Domain\Contract\FakeDataGeneratorInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Hybrid scenario: YAML Data + PHP Logic
 */
final class MyPrismPrism extends AbstractPrism
{
    public function __construct(
        private readonly PrismLoaderInterface $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        FakeDataGeneratorInterface $fakeGenerator,
        DatabaseNameResolverInterface $dbNameResolver
    ) {
        parent::__construct($repository, $tracker, $fakeGenerator, $dbNameResolver);
    }

    public function getName(): PrismName
    {
        return PrismName::fromString('my_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // ======================================================================
        // STEP 1: Load YAML data
        // ======================================================================
        
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // ======================================================================
        // STEP 2: Add complex PHP logic
        // ======================================================================
        
        $this->enrichData($scopeStr);
    }
    
    private function enrichData(string $scope): void
    {
        // Retrieve user created by YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('user_%s', $scope)]
        );
        
        if (empty($users)) {
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Complex logic in PHP
        $this->generateStatistics($userId);
    }
    
    private function generateStatistics(int $userId): void
    {
        $this->insertAndTrack('user_statistics', [
            'user_id' => $userId,
            'score' => rand(1, 100),
            'calculated_at' => new \DateTimeImmutable(),
        ], [
            'calculated_at' => 'datetime_immutable'
        ]);
    }
}
```

### 3. Load the scenario

```bash
php bin/console app:prism:load my_prism --scope=dev
```

---

### Execution Order

When loading a hybrid scenario:

1. **YAML**: `YamlPrism` loads data from YAML file
   - Executes `load:` instructions
   - Automatically tracks created resources
   
2. **PHP**: Your custom logic executes
   - Access to data created in YAML via `getRepository()`
   - Complex business logic with `insertAndTrack()`
   - Calculations, loops, conditions

---

## 🔑 Custom Pivot - Alternative tracking

### What is custom pivot?

**Custom pivot** allows tracking resources by an **alternative column** to `id`. Useful for:

✅ **YAML**: `pivot:` syntax in YAML file  
✅ **PHP**: 4th parameter of `insertAndTrack()`  
✅ **UUID tables**: Track by INT FK rather than VARCHAR  
✅ **Grouped purge**: Delete all resources of an owner  

### Custom pivot in YAML

**In your YAML file** (`prisms/my_prism.yaml`):

```yaml
load:
  # Create a user
  - table: users
    data:
      username: "alice_{{ scope }}"
      email: "alice_{{ scope }}@test.com"
      password: "{{ hash('secret') }}"

  # Messages tracked by user_id
  - table: chat_messages
    data:
      id: "msg_{{ scope }}_{{ uuid }}"
      user_id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      username: "alice_{{ scope }}"
      message: "Message from YAML"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
    pivot:
      id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      column: user_id  # ← Track by user_id instead of id
```

**Result**: The message is tracked with `user_id` instead of its UUID.

### Custom pivot in PHP (hybrid part)

**In your PHP class**:

```php
final class HybridChatPrism extends AbstractPrism
{
    public function __construct(
        private readonly PrismLoaderInterface $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        FakeDataGeneratorInterface $fakeGenerator,
        DatabaseNameResolverInterface $dbNameResolver
    ) {
        parent::__construct($repository, $tracker, $fakeGenerator, $dbNameResolver);
    }

    public function getName(): PrismName
    {
        return PrismName::fromString('hybrid_chat');
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Load YAML (user + first message)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Add more messages in PHP with pivot
        $this->addAdditionalMessages($scopeStr);
    }
    
    private function addAdditionalMessages(string $scope): void
    {
        // Retrieve user created in YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('alice_%s', $scope)]
        );
        
        if (empty($users)) {
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Add 5 additional messages with custom pivot
        for ($i = 2; $i <= 6; $i++) {
            $this->insertAndTrack('chat_messages', [
                'id' => sprintf('msg_%s_%s_%d', $scope, uniqid(), $i),
                'user_id' => $userId,
                'username' => sprintf('alice_%s', $scope),
                'message' => sprintf('Message %d from PHP', $i),
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ], 'user_id'); // ← 4th parameter = tracking column
        }
    }
}
```

### Complete example: Chat with statistics

**YAML file** (`prisms/hybrid_chat_stats.yaml`):

```yaml
load:
  # Users
  - table: users
    data:
      username: "alice_{{ scope }}"
      email: "alice_{{ scope }}@test.com"
      password: "{{ hash('secret') }}"
  
  - table: users
    data:
      username: "bob_{{ scope }}"
      email: "bob_{{ scope }}@test.com"
      password: "{{ hash('secret') }}"

  # Alice's first message with pivot
  - table: chat_messages
    data:
      id: "msg_alice_{{ scope }}_{{ uuid }}"
      user_id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      username: "alice_{{ scope }}"
      message: "Hi Bob!"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
    pivot:
      id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      column: user_id
```

**PHP class**:

```php
final class HybridChatStatsPrism extends AbstractPrism
{
    public function __construct(
        private readonly PrismLoaderInterface $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        FakeDataGeneratorInterface $fakeGenerator,
        DatabaseNameResolverInterface $dbNameResolver
    ) {
        parent::__construct($repository, $tracker, $fakeGenerator, $dbNameResolver);
    }

    public function getName(): PrismName
    {
        return PrismName::fromString('hybrid_chat_stats');
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // 1. Load YAML (users + 1 message)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // 2. Add conversation in PHP
        $this->generateConversation($scopeStr);
        
        // 3. Calculate statistics
        $this->calculateStatistics($scopeStr);
    }
    
    private function generateConversation(string $scope): void
    {
        // Retrieve users
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username FROM users WHERE username LIKE ?',
            [sprintf('%%_%s', $scope)]
        );
        
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['username']] = $user['id'];
        }
        
        // Simulated conversation
        $messages = [
            ['from' => sprintf('bob_%s', $scope), 'text' => 'Hi Alice!'],
            ['from' => sprintf('alice_%s', $scope), 'text' => 'How are you?'],
            ['from' => sprintf('bob_%s', $scope), 'text' => 'Very well, and you?'],
            ['from' => sprintf('alice_%s', $scope), 'text' => 'Great!'],
        ];
        
        foreach ($messages as $msg) {
            $userId = $userMap[$msg['from']];
            
            // Insert with custom pivot
            $this->insertAndTrack('chat_messages', [
                'id' => sprintf('msg_%s_%s', $scope, uniqid()),
                'user_id' => $userId,
                'username' => $msg['from'],
                'message' => $msg['text'],
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ], 'user_id'); // Custom pivot
        }
    }
    
    private function calculateStatistics(string $scope): void
    {
        // Count messages per user
        $stats = $this->getRepository()->executeQuery(
            'SELECT user_id, COUNT(*) as count FROM chat_messages WHERE username LIKE ? GROUP BY user_id',
            [sprintf('%%_%s', $scope)]
        );
        
        foreach ($stats as $stat) {
            $this->insertAndTrack('user_statistics', [
                'user_id' => $stat['user_id'],
                'total_messages' => (int) $stat['count'],
                'calculated_at' => new \DateTimeImmutable(),
            ], [
                'calculated_at' => 'datetime_immutable'
            ]);
        }
    }
}
```

### YAML vs PHP pivot comparison

| Aspect | YAML | PHP |
|--------|------|-----|
| **Syntax** | `pivot:` section in data | 4th param `insertAndTrack()` |
| **Lookup** | `id: {table, where, return}` | Manual retrieval with `executeQuery()` |
| **Simplicity** | ⭐⭐⭐⭐⭐ More declarative | ⭐⭐⭐ More verbose |
| **Flexibility** | ⭐⭐⭐ Limited to lookup | ⭐⭐⭐⭐⭐ Total (calculations, conditions) |

### Typical hybrid use case

**YAML**: Base structure with pivot  
**PHP**: Additional messages generated dynamically with pivot

**Advantages**:
- Simple base data in YAML
- Simulated conversation in PHP (loops, conditions)
- Everything tracked with custom pivot
- Grouped purge by user

### Manual tracking with pivot in PHP

If you need manual tracking (insertion with `getRepository()->insert()`), use `trackResource()` with 3rd parameter:

```php
final class HybridManualTrackingPrism extends AbstractPrism
{
    public function __construct(
        private readonly PrismLoaderInterface $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        FakeDataGeneratorInterface $fakeGenerator,
        DatabaseNameResolverInterface $dbNameResolver
    ) {
        parent::__construct($repository, $tracker, $fakeGenerator, $dbNameResolver);
    }

    public function getName(): PrismName
    {
        return PrismName::fromString('hybrid_manual_tracking');
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Load YAML
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Add with manual tracking
        $this->addMessagesManual($scopeStr);
    }
    
    private function addMessagesManual(string $scope): void
    {
        // Retrieve user created in YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('alice_%s', $scope)]
        );
        
        if (empty($users)) {
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Manual insertion (without insertAndTrack)
        $messageId = $this->getRepository()->insert('chat_messages', [
            'id' => sprintf('msg_manual_%s_%s', $scope, uniqid()),
            'user_id' => $userId,
            'username' => sprintf('alice_%s', $scope),
            'message' => 'Message with manual tracking',
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        // Manual tracking with custom pivot
        $this->trackResource('chat_messages', $userId, 'user_id');
        //                                      ↑         ↑
        //                                      |         Tracking column
        //                                      Value to track
    }
}
```

**Why manual tracking?**

- ✅ Need returned ID before other operations
- ✅ Complex conditional insertion
- ✅ Custom SQL queries with `executeStatement()`
- ✅ Optimized batch inserts

**Comparison**:

| Method | Syntax | Use case |
|---------|---------|-------------|
| `insertAndTrack()` | `insertAndTrack($table, $data, $types, 'user_id')` | ✅ Standard, simple |
| `insert()` + `trackResource()` | `insert(...)`<br/>`trackResource($table, $id, 'user_id')` | ⚙️ Fine control, custom SQL |

---

## 📚 Complete Examples

### Example 1: Enrichment with statistics

**YAML file** (`prisms/hybrid_stats.yaml`):

```yaml
load:
  # Create 3 users
  - table: users
    data:
      username: "user_1_{{ scope }}"
      email: "user_1_{{ scope }}@test.com"
      password: "{{ hash('password') }}"
  
  - table: users
    data:
      username: "user_2_{{ scope }}"
      email: "user_2_{{ scope }}@test.com"
      password: "{{ hash('password') }}"
  
  - table: users
    data:
      username: "user_3_{{ scope }}"
      email: "user_3_{{ scope }}@test.com"
      password: "{{ hash('password') }}"

  # Base messages
  - table: messages
    data:
      user_id:
        table: users
        where:
          username: "user_1_{{ scope }}"
        return: id
      content: "Message 1"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

**PHP class**:

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
use Prism\Application\Prism\YamlPrism;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class HybridStatsPrism extends AbstractPrism
{
    public function __construct(
        private readonly PrismLoaderInterface $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        FakeDataGeneratorInterface $fakeGenerator,
        DatabaseNameResolverInterface $dbNameResolver
    ) {
        parent::__construct($repository, $tracker, $fakeGenerator, $dbNameResolver);
    }

    public function getName(): PrismName
    {
        return PrismName::fromString('hybrid_stats');
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Load YAML data (users + messages)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Enrich with calculated statistics
        $this->generateStatistics($scopeStr);
    }
    
    private function generateStatistics(string $scope): void
    {
        // Retrieve all created users
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username FROM users WHERE username LIKE ?',
            [sprintf('%%_%s', $scope)]
        );
        
        foreach ($users as $user) {
            // Count user's messages
            $messages = $this->getRepository()->executeQuery(
                'SELECT COUNT(*) as count FROM messages WHERE user_id = ?',
                [$user['id']]
            );
            
            $messageCount = (int) $messages[0]['count'];
            
            // Calculate activity score
            $score = $this->calculateScore($messageCount);
            
            // Persist statistics
            $this->insertAndTrack('user_statistics', [
                'user_id' => $user['id'],
                'total_messages' => $messageCount,
                'activity_score' => $score,
                'level' => $this->determineLevel($score),
                'calculated_at' => new \DateTimeImmutable(),
            ], [
                'calculated_at' => 'datetime_immutable'
            ]);
        }
    }
    
    private function calculateScore(int $messageCount): int
    {
        // Complex calculation logic
        $baseScore = $messageCount * 10;
        $bonus = $messageCount > 10 ? 50 : 0;
        return $baseScore + $bonus;
    }
    
    private function determineLevel(int $score): string
    {
        return match (true) {
            $score >= 100 => 'expert',
            $score >= 50 => 'advanced',
            $score >= 20 => 'intermediate',
            default => 'beginner',
        };
    }
}
```

### Example 2: Workflow with state transitions

**YAML file** (`prisms/hybrid_workflow.yaml`):

```yaml
load:
  # User
  - table: users
    data:
      username: "manager_{{ scope }}"
      email: "manager_{{ scope }}@test.com"
      password: "{{ hash('password') }}"

  # Initial project
  - table: projects
    data:
      name: "Project_{{ scope }}"
      manager_id:
        table: users
        where:
          username: "manager_{{ scope }}"
        return: id
      state: "draft"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

**PHP class**:

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\YamlPrism;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class HybridWorkflowPrism extends AbstractPrism
{
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        
        // Load YAML data (user + project)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Simulate workflow
        $this->simulateWorkflow($scope);
    }
    
    private function simulateWorkflow(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Retrieve project created in YAML
        $projects = $this->getRepository()->executeQuery(
            'SELECT id FROM projects WHERE name = ?',
            [sprintf('Project_%s', $scopeStr)]
        );
        
        if (empty($projects)) {
            return;
        }
        
        $projectId = $projects[0]['id'];
        
        // Workflow states
        $states = [
            ['state' => 'draft', 'delay' => 0],
            ['state' => 'review', 'delay' => 1],
            ['state' => 'approved', 'delay' => 2],
            ['state' => 'published', 'delay' => 3],
        ];
        
        $startDate = new \DateTimeImmutable('-3 days');
        
        foreach ($states as $transition) {
            $transitionDate = $startDate->modify(sprintf('+%d days', $transition['delay']));
            
            // Create history
            $this->insertAndTrack('project_history', [
                'project_id' => $projectId,
                'state' => $transition['state'],
                'comment' => $this->generateComment($transition['state']),
                'created_at' => $transitionDate,
            ], [
                'created_at' => 'datetime_immutable'
            ]);
        }
        
        // Update final state
        $this->getRepository()->executeStatement(
            'UPDATE projects SET state = ?, updated_at = ? WHERE id = ?',
            ['published', new \DateTimeImmutable(), $projectId]
        );
        
        $this->logger->info('Workflow simulated', [
            'project_id' => $projectId,
            'nb_transitions' => count($states)
        ]);
    }
    
    private function generateComment(string $state): string
    {
        $comments = [
            'draft' => 'Project created as draft',
            'review' => 'Project sent for review',
            'approved' => 'Project approved by committee',
            'published' => 'Project published and accessible',
        ];
        
        return $comments[$state] ?? sprintf('Transition to %s', $state);
    }
}
```

### Example 3: Dynamic notifications

**YAML file** (`prisms/hybrid_notifications.yaml`):

```yaml
load:
  # Create users
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin_{{ scope }}@test.com"
      password: "{{ hash('password') }}"
      role: "admin"
  
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@test.com"
      password: "{{ hash('password') }}"
      role: "user"
```

**PHP class**:

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\YamlPrism;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class HybridNotificationsPrism extends AbstractPrism
{
    public function load(Scope $scope): void
    {
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        $this->generateNotifications($scope);
    }
    
    private function generateNotifications(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Retrieve all users
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username, role FROM users WHERE username LIKE ?',
            [sprintf('%%_%s', $scopeStr)]
        );
        
        foreach ($users as $user) {
            // Role-specific notifications
            $notifications = $this->getNotificationsForRole($user['role']);
            
            foreach ($notifications as $notif) {
                $this->insertAndTrack('notifications', [
                    'user_id' => $user['id'],
                    'type' => $notif['type'],
                    'message' => sprintf($notif['message'], $user['username']),
                    'priority' => $notif['priority'],
                    'read' => false,
                    'created_at' => new \DateTimeImmutable(),
                ], [
                    'created_at' => 'datetime_immutable'
                ]);
            }
            
            $this->logger->debug('Notifications created', [
                'username' => $user['username'],
                'count' => count($notifications)
            ]);
        }
    }
    
    private function getNotificationsForRole(string $role): array
    {
        $baseNotifications = [
            ['type' => 'info', 'message' => 'Welcome %s!', 'priority' => 1],
            ['type' => 'success', 'message' => 'Account activated for %s', 'priority' => 2],
        ];
        
        if ($role === 'admin') {
            $baseNotifications[] = ['type' => 'warning', 'message' => '%s: remember to configure settings', 'priority' => 3];
            $baseNotifications[] = ['type' => 'info', 'message' => '%s has access to admin tools', 'priority' => 1];
        }
        
        return $baseNotifications;
    }
}
```

---

## 🎲 Fake data generation

Hybrid scenarios can use `{{ fake() }}` in both **YAML** and **PHP** to generate random data.

### Fake in YAML

Use `{{ fake() }}` placeholders directly in your YAML files:

```yaml
# prisms/hybrid_users_fake.yaml
load:
  # User with fake in YAML
  - table: users
    data:
      username: "{{ fake(user) }}"
      email: "{{ fake(email, 'acme.com') }}"
      firstname: "{{ fake(firstname) }}"
      lastname: "{{ fake(lastname) }}"
      phone: "{{ fake(tel, '+33') }}"
      password: "{{ hash('password') }}"
      created_at: "{{ fake(datetime) }}"
    types:
      created_at: datetime_immutable

  # User profile
  - table: user_profiles
    data:
      user_id:
        table: users
        where:
          username: "{{ fake(user) }}"
        return: id
      bio: "{{ fake(text, 200) }}"
      website: "{{ fake(url, 'https') }}"
      company: "{{ fake(company) }}"
```

### Fake in PHP (hybrid part)

Use the `fake()` method in your PHP class:

```php
final class HybridUsersFakePrism extends AbstractPrism
{
    public function load(Scope $scope): void
    {
        // 1. Load base user from YAML
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // 2. Add more users with fake() in PHP
        $this->createAdditionalUsers();
    }
    
    private function createAdditionalUsers(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            // Generate data with fake()
            $userId = $this->insertAndTrack('users', [
                'username' => $this->fake('user'),
                'email' => $this->fake('email', 'test.com'),
                'firstname' => $this->fake('firstname'),
                'lastname' => $this->fake('lastname'),
                'phone' => $this->fake('tel', '+33'),
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ]);
            
            // Profile with fake()
            $this->insertAndTrack('user_profiles', [
                'user_id' => $userId,
                'bio' => $this->fake('text', 200),
                'website' => $this->fake('url', 'https'),
                'company' => $this->fake('company'),
                'favorite_color' => $this->fake('color'),
            ], []);
        }
        
        $this->logger->info('✓ 10 additional users created with fake()');
    }
}
```

### Example: Blog articles with fake

**YAML** - Base structure:

```yaml
# prisms/hybrid_blog_fake.yaml
load:
  # Main author
  - table: users
    data:
      username: "author_{{ scope }}"
      email: "author_{{ scope }}@blog.test"
      firstname: "{{ fake(firstname) }}"
      lastname: "{{ fake(lastname) }}"
      password: "{{ hash('password') }}"

  # First article
  - table: posts
    data:
      author_id:
        table: users
        where:
          username: "author_{{ scope }}"
        return: id
      title: "{{ fake(text, 50) }}"
      slug: "post-{{ scope }}-{{ fake(uuid)|truncate(8) }}"
      content: "{{ fake(text, 500) }}"
      views: "{{ fake(number, 0, 1000) }}"
      published_at: "{{ fake(datetime) }}"
    types:
      published_at: datetime_immutable
```

**PHP** - Mass generation:

```php
final class HybridBlogFakePrism extends AbstractPrism
{
    public function load(Scope $scope): void
    {
        // Load author + first article
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Generate 50 additional articles
        $this->generateArticles($scope);
        
        // Add random comments
        $this->generateComments();
    }
    
    private function generateArticles(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Retrieve author
        $author = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('author_%s', $scopeStr)]
        );
        
        $authorId = $author[0]['id'];
        
        // Generate 50 articles
        for ($i = 2; $i <= 50; $i++) {
            $this->insertAndTrack('posts', [
                'author_id' => $authorId,
                'title' => $this->fake('text', 60),
                'slug' => sprintf('post-%s-%s', $scopeStr, substr($this->fake('uuid'), 0, 8)),
                'content' => $this->fake('text', 1000),
                'views' => $this->fake('number', 0, 10000),
                'published_at' => new \DateTimeImmutable($this->fake('date', 'Y-m-d H:i:s')),
            ], [
                'published_at' => 'datetime_immutable'
            ]);
        }
        
        $this->logger->info('✓ 50 articles generated with fake()');
    }
    
    private function generateComments(): void
    {
        // Retrieve all posts
        $posts = $this->getRepository()->executeQuery(
            'SELECT id FROM posts ORDER BY id'
        );
        
        // 3-7 comments per article
        foreach ($posts as $post) {
            $nbComments = $this->fake('number', 3, 7);
            
            for ($i = 0; $i < $nbComments; $i++) {
                $this->insertAndTrack('comments', [
                    'post_id' => $post['id'],
                    'author' => $this->fake('firstname') . ' ' . $this->fake('lastname'),
                    'email' => $this->fake('email'),
                    'content' => $this->fake('text', 150),
                    'ip' => $this->fake('ip'),
                    'created_at' => new \DateTimeImmutable(),
                ], [
                    'created_at' => 'datetime_immutable'
                ]);
            }
        }
        
        $this->logger->info('✓ Comments generated with fake()');
    }
}
```

### Combining fake + variables

You can combine `fake()` with variables to reuse certain values:

```yaml
# YAML
vars:
  # Fixed value
  admin_email: "admin_{{ scope }}@test.com"
  
  # Fake value stored in variable
  random_company: "{{ fake(company) }}"

load:
  - table: users
    data:
      email: "{{ $admin_email }}"           # Fixed value
      company: "{{ $random_company }}"      # Same fake everywhere
      phone: "{{ fake(tel) }}"               # New fake each time
```

```php
// PHP
public function load(Scope $scope): void
{
    $yamlPrism = new YamlPrism(...);
    $yamlPrism->load($scope);
    
    // Store a fake value for reuse
    $companyName = $this->fake('company');
    
    for ($i = 1; $i <= 5; $i++) {
        $this->insertAndTrack('employees', [
            'name' => $this->fake('firstname') . ' ' . $this->fake('lastname'),
            'email' => $this->fake('email', 'corp.com'),
            'company' => $companyName, // ✅ Same company for all
        ], []);
    }
}
```

### Available fake types

All `fake()` types are available in both **YAML** and **PHP**. See complete documentation:

- **[YAML Guide - Fake section](PRISM_YAML.md#fake)** - YAML syntax
- **[PHP Guide - Fake section](PRISM_PHP.md#random-data-generation)** - PHP API

**Main types**:
- Identity: `user`, `email`, `firstname`, `lastname`, `company`
- IDs: `id`, `uuid`
- Dates: `date`, `datetime`
- Files: `pathfile`, `pathdir`
- Text: `text`, `number`
- Network: `url`, `ip`, `ipv6`, `mac`, `tel`
- Misc: `color`, `boolean`

---

## 📋 Best Practices

### 1. Keep YAML simple

```yaml
# ✅ Good: clear structure in YAML
load:
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@test.com"

# ❌ Bad: complex logic in YAML (impossible)
# Use PHP for calculations
```

### 2. Use PHP for business logic

```php
// ✅ Good: calculations in PHP
private function calculateScore(int $actions): int
{
    return $actions > 10 ? $actions * 2 : $actions;
}

// ❌ Bad: trying to do calculations in YAML
```

### 3. Retrieve YAML data before enriching

```php
public function load(Scope $scope): void
{
    // ✅ Good: load YAML first
    $yamlPrism = new YamlPrism(...);
    $yamlPrism->load($scope);
    
    // Then enrich
    $this->enrichData($scope);
}

// ❌ Bad: forget to call YAML load
```

### 4. Log enrichments

```php
private function generateStatistics(Scope $scope): void
{
    // ... logic ...
    
    $this->logger->info('Statistics generated', [
        'scope' => $scope->toString(),
        'count' => $statsCount
    ]);
}
```

### 5. Handle cases where YAML data doesn't exist

```php
private function enrichData(Scope $scope): void
{
    $users = $this->getRepository()->executeQuery(...);
    
    // ✅ Good: check before continuing
    if (empty($users)) {
        $this->logger->warning('No users found for enrichment');
        return;
    }
    
    // Enrichment logic...
}
```

---

## 🎯 When to use hybrid scenarios?

### ✅ Ideal use cases

- **Progressive prototyping**: Start with YAML, add PHP gradually
- **Reference data + calculations**: Base structure in YAML, calculated enrichment in PHP
- **Simple workflows**: Initial state in YAML, transitions in PHP
- **Tests with variations**: Fixed data in YAML, dynamic variations in PHP

### ❌ Avoid hybrids when...

- **Everything is simple** → Use pure YAML ([YAML Guide](PRISM_YAML.md))
- **Everything is complex** → Use pure PHP ([PHP Guide](PRISM_PHP.md))
- **No business logic** → YAML is enough
- **No repetitive structure** → Pure PHP clearer

---

## 🔗 Resources

- **[Main Documentation](PRISM.md)** - System overview
- **[YAML Guide](PRISM_YAML.md)** - Complete YAML syntax
- **[PHP Guide](PRISM_PHP.md)** - Complete PHP API
- **[Faker Guide](PRISM_FAKER.md)** - Random data generation
