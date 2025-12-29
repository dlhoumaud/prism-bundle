# Support Multi-Database

## 📋 Vue d'ensemble

PrismBundle supporte nativement **plusieurs bases de données** dans vos scénarios. Vous pouvez :

✅ Charger des données dans différentes bases en un seul scénario  
✅ Tracker les ressources par base de données  
✅ Purger automatiquement les données multi-bases  
✅ Utiliser des lookups cross-database  

---

## 🔧 Configuration

### Étape 1 : Configurer Doctrine avec plusieurs connexions

Dans `config/packages/doctrine.yaml` :

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

### Étape 2 : Ajouter les variables d'environnement

Dans `.env` :

```env
DATABASE_URL="mysql://user:pass@localhost:3306/hexagonal?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
DATABASE_SECONDARY_URL="mysql://user:pass@localhost:3306/hexagonal_secondary?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
DATABASE_LOGS_URL="mysql://user:pass@localhost:3306/hexagonal_logs?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

### Étape 3 : Créer les bases de données

```bash
php bin/console doctrine:database:create --connection=default
php bin/console doctrine:database:create --connection=secondary
php bin/console doctrine:database:create --connection=logs
```

Ou avec Docker :

```sql
-- docker/db/init.sql
CREATE DATABASE IF NOT EXISTS hexagonal;
CREATE DATABASE IF NOT EXISTS hexagonal_secondary;
CREATE DATABASE IF NOT EXISTS hexagonal_logs;
```

---

## 📝 Utilisation en YAML

### Syntaxe de base

Le paramètre `db` est **optionnel** sur toutes les instructions :

```yaml
load:
  - table: users
    # Pas de db → base par défaut
    data:
      username: "admin_{{ scope }}"

  - table: audit_logs
    db: hexagonal_secondary  # Base secondaire
    data:
      action: "user_created"
      
purge:
  - table: audit_logs
    db: hexagonal_secondary
    where:
      action: "user_created"
```

### 🔗 Résolution des Connexions Doctrine

PrismBundle supporte **deux syntaxes** pour spécifier la base de données cible :

#### Syntaxe 1 : Nom de base de données (direct)

```yaml
load:
  - table: users
    db: hexagonal_secondary  # Nom exact de la base de données
    data:
      username: "admin_{{ scope }}"
```

Utilisez le **nom exact de la base de données** tel qu'il apparaît dans l'URL de connexion.

#### Syntaxe 2 : Nom de connexion Doctrine (avec %)

```yaml
load:
  - table: users
    db: %secondary%  # Nom de la connexion Doctrine
    data:
      username: "admin_{{ scope }}"
      
  - table: audit_logs
    db: %logs%  # Autre connexion
    data:
      action: "user_created"
```

La syntaxe `%connection_name%` est résolue automatiquement par le **DatabaseNameResolver** :

1. Détecte le pattern `%nom%` via regex
2. Récupère la connexion Doctrine correspondante
3. Extrait le nom de la base depuis les paramètres de connexion
4. Utilise ce nom pour les requêtes SQL

**Avantages de la syntaxe `%connection%`** :

✅ **Abstraction** : Découple le scénario du nom exact de la base  
✅ **Flexibilité** : Changez le nom de la base sans modifier les scénarios  
✅ **Clarté** : Reflète la configuration Doctrine (secondary, logs, etc.)  
✅ **Cohérence** : Même nomenclature que dans `doctrine.yaml`  

**Exemple complet** :

```yaml
# prisms/multi_db_example.yaml
load:
  # Base par défaut (pas de db)
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@example.com"

  # Syntaxe directe (nom de base)
  - table: users
    db: hexagonal_secondary
    data:
      username: "user_{{ scope }}"
      email: "user@example.com"

  # Syntaxe Doctrine (nom de connexion)
  - table: users
    db: %secondary%
    data:
      username: "user2_{{ scope }}"
      email: "user2@example.com"

  # Logs dans base dédiée
  - table: audit_logs
    db: %logs%
    data:
      action: "user_created"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

**Lookups cross-database avec %connection%** :

```yaml
load:
  - table: sync_status
    db: %secondary%
    data:
      user_id:
        table: users
        # Lookup dans base par défaut
        where:
          username: "admin_{{ scope }}"
        return: id
      last_sync: "{{ now }}"
```

### Exemple complet : Logs d'audit

```yaml
# prisms/audit_trail.yaml
load:
  # Créer un utilisateur dans la base principale
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@example.com"
      password: "{{ hash('admin123') }}"

  # Logger l'action dans la base de logs
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

  # Créer un article dans la base principale
  - table: posts
    data:
      author_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      title: "Article {{ scope }}"

  # Logger la création de l'article
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
  # Purger les logs (exécuté AVANT le purge automatique)
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "user_created"
      
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "post_created"
```

### Lookups avec databases

Les **lookups** peuvent référencer des tables dans d'autres bases :

```yaml
load:
  - table: sync_status
    db: hexagonal_secondary
    data:
      user_id:
        table: users           # Base par défaut
        where:
          username: "admin_{{ scope }}"
        return: id
      last_sync: "{{ now }}"
      
  - table: reports
    data:
      author_id:
        table: users
        db: hexagonal_secondary  # Lookup dans base secondaire
        where:
          email: "admin@example.com"
        return: id
```

---

## 🐘 Utilisation en PHP

### insertAndTrack() avec dbName

```php
public function load(Scope $scope): void
{
    $scopeStr = $scope->toString();
    
    // Insertion dans la base par défaut
    $userId = $this->insertAndTrack('users', [
        'username' => sprintf('admin_%s', $scopeStr),
        'email' => sprintf('admin_%s@example.com', $scopeStr),
    ], []);
    
    // Insertion dans la base de logs
    $logId = $this->insertAndTrack('audit_logs', [
        'user_id' => $userId,
        'action' => 'user_created',
        'created_at' => new \DateTimeImmutable(),
    ], [
        'created_at' => 'datetime_immutable'
    ], 'id', 'hexagonal_logs');  // 5e paramètre : dbName
    
    // Insertion avec pivot custom dans base secondaire
    $statusId = $this->insertAndTrack('sync_status', [
        'user_id' => $userId,
        'status' => 'active',
    ], [], 'user_id', 'hexagonal_secondary');
}
```

### trackResource() avec dbName

```php
// Tracking manuel dans une base secondaire
$this->trackResource(
    'audit_logs',           // table
    $userId,                // rowId
    'user_id',              // idColumnName
    'hexagonal_logs'        // dbName
);
```

### Signature complète

```php
protected function insertAndTrack(
    string $tableName,
    array $data,
    array $types = [],
    string $idColumnName = 'id',
    ?string $dbName = null  // ← Nouveau paramètre
): int|string|null

protected function trackResource(
    string $tableName,
    int|string $rowId,
    string $idColumnName = 'id',
    ?string $dbName = null  // ← Nouveau paramètre
): void
```

---

## 🔍 Fonctionnement interne

### Table prism_resource

La table de tracking contient une colonne `db_name` :

```sql
CREATE TABLE prism_resource (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prism_name VARCHAR(255) NOT NULL,
    scope VARCHAR(255) NOT NULL,
    table_name VARCHAR(255) NOT NULL,
    row_id VARCHAR(255) NOT NULL,
    id_column_name VARCHAR(255) NOT NULL DEFAULT 'id',
    db_name VARCHAR(255) NULL,  -- ← Nouveau
    created_at DATETIME NOT NULL,
    INDEX idx_prism_scope (prism_name, scope),
    INDEX idx_table_row (table_name, row_id),
    INDEX idx_db_name (db_name)
);
```

### Purge automatique

Lors du purge, si `db_name` est renseigné :

```php
// Construit : DELETE FROM hexagonal_logs.audit_logs WHERE id = ?
$query = sprintf(
    'DELETE FROM %s.%s WHERE %s = ?',
    $resource->getDbName(),    // hexagonal_logs
    $resource->getTableName(), // audit_logs
    $resource->getIdColumnName()
);
```

Sans `db_name` :

```php
// Construit : DELETE FROM users WHERE id = ?
$query = sprintf(
    'DELETE FROM %s WHERE %s = ?',
    $resource->getTableName(),
    $resource->getIdColumnName()
);
```

---

## 🎯 Cas d'usage

### 1. Séparation Données / Logs

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
  # Service utilisateurs
  - table: users
    db: users_service
    data:
      username: "user_{{ scope }}"

  # Service commandes
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

### 3. Données sensibles séparées

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

### 4. Archivage

```yaml
purge:
  # Déplacer vers archive avant suppression
  - table: deleted_users
    db: archive_db
    # Custom insert via scénario PHP...

  # Puis supprimer de la base principale
  - table: users
    where:
      username: "user_{{ scope }}"
```

---

## ⚠️ Limitations et bonnes pratiques

### ✅ Supporté

- ✅ Multiples bases sur le même serveur MySQL/MariaDB/PostgreSQL
- ✅ Lookups cross-database (même serveur)
- ✅ Tracking et purge automatique multi-bases
- ✅ Transactions par base (une transaction par connexion)

### ❌ Non supporté

- ❌ Transactions distribuées (2PC) entre bases
- ❌ Bases de données de types différents (ex: MySQL + PostgreSQL)
- ❌ Jointures SQL cross-database dans les lookups

### 💡 Bonnes pratiques

1. **Nommez clairement vos bases** :
   ```yaml
   db: hexagonal_logs      # ✅ Clair
   db: db2                 # ❌ Vague
   ```

2. **Documentez l'usage** :
   ```yaml
   # Logs d'audit - base hexagonal_logs
   - table: audit_logs
     db: hexagonal_logs
   ```

3. **Cohérence dans les lookups** :
   ```yaml
   # Si la table source est dans une base spécifique, précisez-le
   user_id:
     table: users
     db: users_service  # Explicite
     where:
       username: "admin"
     return: id
   ```

4. **Purge custom pour nettoyage cross-base** :
   ```yaml
   purge:
     # Purge explicite dans chaque base
     - table: logs
       db: logs_db
       where:
         created_at: "< {{ date('-7 days') }}"
   ```

---

## 🔗 Ressources

- [Configuration Doctrine](https://symfony.com/doc/current/doctrine.html#multiple-entity-managers)
- [Guide YAML](PRISM_YAML.md) - Syntaxe complète
- [Guide PHP](PRISM_PHP.md) - API complète
- [PrismOffice](../../PrismOffice/README.md) - Interface visuelle avec support multi-base

---

## 📊 Exemple complet : Application avec audit

```yaml
# prisms/complete_audit.yaml
vars:
  admin: "admin_{{ scope }}"
  
load:
  # 1. Utilisateur
  - table: users
    data:
      username: "{{ $admin }}"
      email: "{{ $admin }}@example.com"
      password: "{{ hash('admin123') }}"

  # 2. Log de création
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

  # 3. Rôle admin
  - table: user_roles
    data:
      user_id:
        table: users
        where:
          username: "{{ $admin }}"
        return: id
      role: "ROLE_ADMIN"

  # 4. Log d'assignation de rôle
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
  # Purger les logs en premier (pas de FK)
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "user_created"
      
  - table: audit_logs
    db: hexagonal_logs
    where:
      action: "role_assigned"
```

**Commandes** :

```bash
# Charger le scénario
php bin/console app:prism:load complete_audit --scope=demo

# Vérifier les données
# Base principale
mysql -e "SELECT * FROM hexagonal.users WHERE username LIKE 'admin_demo%'"
mysql -e "SELECT * FROM hexagonal.user_roles"

# Base de logs
mysql -e "SELECT * FROM hexagonal_logs.audit_logs"

# Purger tout
php bin/console app:prism:purge complete_audit --scope=demo
```
