# Multi-Database Support

## 📋 Overview

PrismBundle natively supports **multiple databases** in your scenarios. You can:

✅ Load data into different databases in a single scenario  
✅ Track resources by database  
✅ Automatically purge multi-database data  
✅ Use cross-database lookups  

---

## 🔧 Configuration

### Step 1: Configure Doctrine with multiple connections

In `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                url: '%env(resolve:DATABASE_URL)%'
            secondary:
                url: '%env(resolve:DATABASE_SECONDARY_URL)%'
            logs:
                url: '%env(resolve:DATABASE_LOGS_URL)%'
```

### Step 2: Add environment variables

In `.env`:

```env
DATABASE_URL="mysql://user:pass@localhost:3306/hexagonal?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
DATABASE_SECONDARY_URL="mysql://user:pass@localhost:3306/hexagonal_secondary?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
DATABASE_LOGS_URL="mysql://user:pass@localhost:3306/hexagonal_logs?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

### Step 3: Create databases

```bash
php bin/console doctrine:database:create --connection=default
php bin/console doctrine:database:create --connection=secondary
php bin/console doctrine:database:create --connection=logs
```

Or with Docker:

```sql
-- docker/db/init.sql
CREATE DATABASE IF NOT EXISTS hexagonal;
CREATE DATABASE IF NOT EXISTS hexagonal_secondary;
CREATE DATABASE IF NOT EXISTS hexagonal_logs;
```

---

## 📝 YAML Usage

### Basic syntax

The `db` parameter is **optional** on all instructions:

```yaml
load:
  - table: users
    # No db → default database
    data:
      username: "admin_{{ scope }}"

  - table: audit_logs
    db: hexagonal_secondary  # Secondary database
    data:
      action: "user_created"
      
purge:
  - table: audit_logs
    db: hexagonal_secondary
    where:
      action: "user_created"
```

### 🔗 Doctrine Connection Resolution

PrismBundle supports **two syntaxes** to specify the target database:

#### Syntax 1: Database name (direct)

```yaml
load:
  - table: users
    db: hexagonal_secondary  # Exact database name
    data:
      username: "admin_{{ scope }}"
```

Use the **exact database name** as it appears in the connection URL.

#### Syntax 2: Doctrine connection name (with %)

```yaml
load:
  - table: users
    db: %secondary%  # Doctrine connection name
    data:
      username: "admin_{{ scope }}"
      
  - table: audit_logs
    db: %logs%  # Another connection
    data:
      action: "user_created"
```

The `%connection_name%` syntax is automatically resolved by the **DatabaseNameResolver**:

1. Detects the `%name%` pattern via regex
2. Retrieves the corresponding Doctrine connection
3. Extracts the database name from connection parameters
4. Uses this name for SQL queries

**Benefits of `%connection%` syntax**:

✅ **Abstraction**: Decouples scenario from exact database name  
✅ **Flexibility**: Change database name without modifying scenarios  
✅ **Clarity**: Reflects Doctrine configuration (secondary, logs, etc.)  
✅ **Consistency**: Same nomenclature as in `doctrine.yaml`  

**Complete example**:

```yaml
# prisms/multi_db_example.yaml
load:
  # Default database (no db)
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@example.com"

  # Direct syntax (database name)
  - table: users
    db: hexagonal_secondary
    data:
      username: "user_{{ scope }}"
      email: "user@example.com"

  # Doctrine syntax (connection name)
  - table: users
    db: %secondary%
    data:
      username: "user2_{{ scope }}"
      email: "user2@example.com"

  # Logs in dedicated database
  - table: audit_logs
    db: %logs%
    data:
      action: "user_created"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

**Cross-database lookups with %connection%**:

```yaml
load:
  - table: sync_status
    db: %secondary%
    data:
      user_id:
        table: users
        # Lookup in default database
        where:
          username: "admin_{{ scope }}"
        return: id
      last_sync: "{{ now }}"
```

### Complete example: Audit logs

```yaml
# prisms/audit_trail.yaml
load:
  # Create user in main database
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@example.com"
      password: "{{ hash('admin123') }}"

  # Log action in logs database
  - table: audit_logs
    db: hexagonal_logs
    data:
      user_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      action: "user_created"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable

  # Create article in main database
  - table: posts
    data:
      author_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      title: "Article {{ scope }}"

  # Log article creation
  - table: audit_logs
    db: hexagonal_logs
    data:
      user_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      action: "post_created"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable

purge:
  # Purge logs (executed BEFORE automatic purge)
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "user_created"
      
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "post_created"
```

### Lookups with databases

**Lookups** can reference tables in other databases:

```yaml
load:
  - table: sync_status
    db: hexagonal_secondary
    data:
      user_id:
        table: users           # Default database
        where:
          username: "admin_{{ scope }}"
        return: id
      last_sync: "{{ now }}"
      
  - table: reports
    data:
      author_id:
        table: users
        db: hexagonal_secondary  # Lookup in secondary database
        where:
          email: "admin@example.com"
        return: id
```

---

## 🐘 PHP Usage

### insertAndTrack() with dbName

```php
public function load(Scope $scope): void
{
    $scopeStr = $scope->toString();
    
    // Insert in default database
    $userId = $this->insertAndTrack('users', [
        'username' => sprintf('admin_%s', $scopeStr),
        'email' => sprintf('admin_%s@example.com', $scopeStr),
    ], []);
    
    // Insert in logs database
    $logId = $this->insertAndTrack('audit_logs', [
        'user_id' => $userId,
        'action' => 'user_created',
        'created_at' => new \DateTimeImmutable(),
    ], [
        'created_at' => 'datetime_immutable'
    ], 'id', 'hexagonal_logs');  // 5th parameter: dbName
    
    // Insert with custom pivot in secondary database
    $statusId = $this->insertAndTrack('sync_status', [
        'user_id' => $userId,
        'status' => 'active',
    ], [], 'user_id', 'hexagonal_secondary');
}
```

### trackResource() with dbName

```php
// Manual tracking in secondary database
$this->trackResource(
    'audit_logs',           // table
    $userId,                // rowId
    'user_id',              // idColumnName
    'hexagonal_logs'        // dbName
);
```

### Complete signature

```php
protected function insertAndTrack(
    string $tableName,
    array $data,
    array $types = [],
    string $idColumnName = 'id',
    ?string $dbName = null  // ← New parameter
): int|string|null

protected function trackResource(
    string $tableName,
    int|string $rowId,
    string $idColumnName = 'id',
    ?string $dbName = null  // ← New parameter
): void
```

---

## 🔍 Internal functioning

### prism_resource table

The tracking table contains a `db_name` column:

```sql
CREATE TABLE prism_resource (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prism_name VARCHAR(255) NOT NULL,
    scope VARCHAR(255) NOT NULL,
    table_name VARCHAR(255) NOT NULL,
    row_id VARCHAR(255) NOT NULL,
    id_column_name VARCHAR(255) NOT NULL DEFAULT 'id',
    db_name VARCHAR(255) NULL,  -- ← New
    created_at DATETIME NOT NULL,
    INDEX idx_prism_scope (prism_name, scope),
    INDEX idx_table_row (table_name, row_id),
    INDEX idx_db_name (db_name)
);
```

### Automatic purge

During purge, if `db_name` is set:

```php
// Builds: DELETE FROM hexagonal_logs.audit_logs WHERE id = ?
$query = sprintf(
    'DELETE FROM %s.%s WHERE %s = ?',
    $resource->getDbName(),    // hexagonal_logs
    $resource->getTableName(), // audit_logs
    $resource->getIdColumnName()
);
```

Without `db_name`:

```php
// Builds: DELETE FROM users WHERE id = ?
$query = sprintf(
    'DELETE FROM %s WHERE %s = ?',
    $resource->getTableName(),
    $resource->getIdColumnName()
);
```

---

## 🎯 Use cases

### 1. Data / Logs separation

```yaml
load:
  - table: users
    data:
      username: "user_{{ scope }}"

  - table: application_logs
    db: logs_db
    data:
      message: "User created"
      level: "info"
```

### 2. Microservices

```yaml
load:
  # Users service
  - table: users
    db: users_service
    data:
      username: "user_{{ scope }}"

  # Orders service
  - table: orders
    db: orders_service
    data:
      user_id:
        table: users
        db: users_service
        where:
          username: "user_{{ scope }}"
        return: id
      total: 100
```

### 3. Separated sensitive data

```yaml
load:
  - table: users
    data:
      username: "user_{{ scope }}"

  - table: user_secrets
    db: secure_vault
    data:
      user_id:
        table: users
        where:
          username: "user_{{ scope }}"
        return: id
      api_key: "{{ uuid }}"
      secret_token: "{{ hash('secret') }}"
```

### 4. Archiving

```yaml
purge:
  # Move to archive before deletion
  - table: deleted_users
    db: archive_db
    # Custom insert via PHP scenario...

  # Then delete from main database
  - table: users
    where:
      username: "user_{{ scope }}"
```

---

## ⚠️ Limitations and best practices

### ✅ Supported

- ✅ Multiple databases on the same MySQL/MariaDB/PostgreSQL server
- ✅ Cross-database lookups (same server)
- ✅ Automatic multi-database tracking and purge
- ✅ Transactions per database (one transaction per connection)

### ❌ Not supported

- ❌ Distributed transactions (2PC) between databases
- ❌ Different database types (e.g., MySQL + PostgreSQL)
- ❌ Cross-database SQL joins in lookups

### 💡 Best practices

1. **Clearly name your databases**:
   ```yaml
   db: hexagonal_logs      # ✅ Clear
   db: db2                 # ❌ Vague
   ```

2. **Document usage**:
   ```yaml
   # Audit logs - hexagonal_logs database
   - table: audit_logs
     db: hexagonal_logs
   ```

3. **Consistency in lookups**:
   ```yaml
   # If source table is in a specific database, specify it
   user_id:
     table: users
     db: users_service  # Explicit
     where:
       username: "admin"
     return: id
   ```

4. **Custom purge for cross-database cleanup**:
   ```yaml
   purge:
     # Explicit purge in each database
     - table: logs
       db: logs_db
       where:
         created_at: "< {{ date('-7 days') }}"
   ```

---

## 🔗 Resources

- [Doctrine Configuration](https://symfony.com/doc/current/doctrine.html#multiple-entity-managers)
- [YAML Guide](PRISM_YAML.md) - Complete syntax
- [PHP Guide](PRISM_PHP.md) - Complete API
- [PrismOffice](../../PrismOffice/README.md) - Visual interface with multi-database support

---

## 📊 Complete example: Application with audit

```yaml
# prisms/complete_audit.yaml
vars:
  admin: "admin_{{ scope }}"
  
load:
  # 1. User
  - table: users
    data:
      username: "{{ $admin }}"
      email: "{{ $admin }}@example.com"
      password: "{{ hash('admin123') }}"

  # 2. Creation log
  - table: audit_logs
    db: hexagonal_logs
    data:
      user_id:
        table: users
        where:
          username: "{{ $admin }}"
        return: id
      action: "user_created"
      ip_address: "127.0.0.1"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable

  # 3. Admin role
  - table: user_roles
    data:
      user_id:
        table: users
        where:
          username: "{{ $admin }}"
        return: id
      role: "ROLE_ADMIN"

  # 4. Role assignment log
  - table: audit_logs
    db: hexagonal_logs
    data:
      user_id:
        table: users
        where:
          username: "{{ $admin }}"
        return: id
      action: "role_assigned"
      details: "ROLE_ADMIN"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable

purge:
  # Purge logs first (no FK)
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "user_created"
      
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "role_assigned"
```

**Commands**:

```bash
# Load scenario
php bin/console app:prism:load complete_audit --scope=demo

# Verify data
# Main database
mysql -e "SELECT * FROM hexagonal.users WHERE username LIKE 'admin_demo%'"
mysql -e "SELECT * FROM hexagonal.user_roles"

# Logs database
mysql -e "SELECT * FROM hexagonal_logs.audit_logs"

# Purge everything
php bin/console app:prism:purge complete_audit --scope=demo
```
