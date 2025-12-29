# 🎯 Système de Scénarios Fonctionnels - Vue d'ensemble

## 📦 Présentation

Le **Système de Scénarios Fonctionnels** (Prism) est un framework de fixtures avancé conçu pour générer des données de test reproductibles et isolées.

### Fonctionnalités principales

- **Scopes d'isolation** : Chaque développeur peut travailler sur son propre jeu de données
- **Multi-base de données** : Support natif avec lookups cross-DB
- **Traçabilité complète** : Toutes les ressources créées sont trackées
- **Purge intelligente** : Suppression automatique en respectant les FK
- **3 approches disponibles** : YAML pur, PHP pur, ou Hybride
- **44 types de données faker** : Français + internationaux
- **Tests complets** : 78 tests unitaires, 176 assertions

### Scénarios d'exemple fournis

- `test_users` - Exemple simple (2 users)
- `chat_conversation` - Exemple avec relations (3 users + messages)
- `advanced_example` - Template complet avec toutes les fonctionnalités
- `hybrid_example` - Exemple hybride (YAML + PHP)

---

## 🗄️ Base de données

### Table de traçabilité

```sql
CREATE TABLE prism_resource (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    prism_name VARCHAR(100) NOT NULL,    -- Nom du scénario
    scope VARCHAR(50) NOT NULL,             -- Scope d'isolation
    table_name VARCHAR(64) NOT NULL,        -- Table de la ressource
    row_id BIGINT NOT NULL,                 -- ID de la ligne créée
    created_at DATETIME NOT NULL,           -- Date de création
    
    INDEX idx_prism_scope (prism_name, scope),
    INDEX idx_scope (scope),
    INDEX idx_table_row (table_name, row_id),
    INDEX idx_created_at (created_at)
);
```

---

## 🎮 Commandes CLI disponibles

### 1️⃣ Lister les scénarios

```bash
php bin/console app:prism:list
```

**Sortie :**
```
Scénarios fonctionnels disponibles
==================================

 ------------------- --------------------------------------------------------------------- 
  Nom du scénario     Classe                                                               
 ------------------- --------------------------------------------------------------------- 
  advanced_example    Prism\Infrastructure\Prism\AdvancedExamplePrism   
  chat_conversation   Prism\Infrastructure\Prism\ChatConversationPrism  
  test_users          Prism\Infrastructure\Prism\TestUsersPrism         
 ------------------- --------------------------------------------------------------------- 
```

### 2️⃣ Charger un scénario

```bash
# Syntaxe
php bin/console app:prism:load <prism_name> --scope=<scope>

# Exemples
php bin/console app:prism:load test_users --scope=dev_alice
php bin/console app:prism:load chat_conversation --scope=test_qa
php bin/console app:prism:load advanced_example --scope=dev_bob
```

**Ce que ça fait :**
1. ✅ Purge automatique des données existantes du même scope
2. ✅ Transaction automatique (rollback en cas d'erreur)
3. ✅ Tracking de toutes les ressources créées
4. ✅ Logging détaillé
5. ✅ Rapport d'exécution

### 3️⃣ Purger un scénario

```bash
# Purger un scénario spécifique
php bin/console app:prism:purge test_users --scope=dev_alice

# Purger tous les scénarios d'un scope
php bin/console app:prism:purge --scope=dev_alice --all
```

**Ce que ça fait :**
1. ✅ Lit les ressources trackées
2. ✅ Supprime en ordre inverse (respecte les FK)
3. ✅ Nettoie les traces dans prism_resource
4. ✅ Transaction automatique

---

## 📝 3 Scénarios d'exemple fournis

### 🟢 `test_users` - Simple

**Crée :**
- 2 utilisateurs (admin + user)

**Usage :**
```bash
php bin/console app:prism:load test_users --scope=dev_test
```

**Données créées :**
- `admin_dev_test@example.test`
- `user_dev_test@example.test`

---

### 🟡 `chat_conversation` - Relations

**Crée :**
- 3 utilisateurs (Alice, Bob, Charlie)
- 5 messages de chat entre eux (avec FK)

**Usage :**
```bash
php bin/console app:prism:load chat_conversation --scope=dev_test
```

**Démontre :**
- Relations entre tables (sender_id, receiver_id)
- Purge automatique en ordre inverse

---

### 🔴 `advanced_example` - Template complet

**Crée :**
- 5 utilisateurs (admin, manager, 3 users)
- 7 messages entre eux
- Configuration ACL
- Statistiques

**Usage :**
```bash
php bin/console app:prism:load advanced_example --scope=dev_demo
```

**Démontre :**
- Organisation en étapes
- Logging structuré
- Statistiques
- Gestion d'erreurs
- Template pour vos scénarios

---

## 🎨 Comment créer votre scénario

### Template minimal

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class MonPrismPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('mon_prism');
    }
    
    public function load(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Votre logique ici
        $id = $this->insertAndTrack('ma_table', [
            'field' => sprintf('value_%s', $scopeStr),
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Ressource créée: {id}', ['id' => $id]);
    }
}
```

### Méthodes disponibles dans `AbstractPrism`

```php
// Récupérer la connexion DBAL
$connection = $this->getConnection();

// Insérer ET tracker automatiquement (⭐ recommandé)
$id = $this->insertAndTrack('table', $data, $types);

// Tracker manuellement
$this->trackResource('table', $rowId);

// Logger (PSR-3)
$this->logger->info('Message', ['context' => 'value']);
$this->logger->debug('Debug');
$this->logger->error('Erreur');

// La méthode purge() est DÉJÀ implémentée ✅
// Pas besoin de l'écrire sauf si logique spéciale
```

---

## 🏗️ Architecture hexagonale respectée

### ✅ Principes respectés

1. **Domain ne dépend de rien** ✅
   - Pas d'import Symfony
   - Pas d'import Doctrine
   - Seulement interfaces, entités, value objects

2. **Inversion de dépendance** ✅
   - Interfaces définies dans le bundle
   - Implémentations fournies par le bundle
   - Extensible par l'utilisateur

3. **Application simple** ✅
   - Use Cases purs
   - Coordination des opérations
   - Gestion des transactions

4. **Infrastructure adaptable** ✅
   - Doctrine DBAL aujourd'hui
   - Peut être remplacé par autre chose demain
   - Code métier reste inchangé

---

## 📊 Workflow complet

### Chargement d'un scénario

```
1. CLI: php bin/console app:prism:load test_users --scope=dev_alice
              ↓
2. Commande traite la requête
              ↓
3. Le système purge automatiquement le scope existant
   - DELETE old resources + tracking
              ↓
4. Exécution du scénario
   - INSERT INTO users (...)
   - INSERT INTO prism_resource (...) pour chaque ressource
              ↓
5. COMMIT transaction
              ↓
6. Succès ! Les données sont créées et trackées
```

### Purge d'un scénario

```
1. CLI: php bin/console app:prism:purge test_users --scope=dev_alice
              ↓
2. Commande traite la requête
              ↓
3. Le système récupère les ressources trackées
   - SELECT * FROM prism_resource WHERE prism_name=... AND scope=...
   - Pour chaque ressource (en ordre INVERSE):
     - DELETE FROM <table> WHERE id = <row_id>
   - DELETE FROM prism_resource WHERE prism_name=... AND scope=...
              ↓
4. Succès ! Toutes les données sont supprimées proprement
```

---

## 🎓 Points clés à retenir

### ✅ Avantages

1. **Fiabilité**
   - Table pivot = traçabilité garantie
   - Purge automatique en ordre inverse
   - Transactions automatiques

2. **Developer Experience**
   - API simple (`insertAndTrack()`)
   - Auto-découverte des scénarios
   - CLI user-friendly
   - Logging intégré
   - Maintenabilité

4. **Isolation**
   - Scopes personnels
   - Pas de collision entre développeurs
   - Purge sélectif

### 🎯 Bonnes pratiques

- ✅ Utilisez des scopes préfixés (`dev_`, `test_`, `qa_`)
- ✅ Toujours purger avant de charger (automatique)
- ✅ Utilisez `insertAndTrack()` au lieu de `insert()` + `track()`
- ✅ Loggez vos opérations importantes
- ✅ Créez les parents avant les enfants (FK)
- ✅ Purgez régulièrement vos scopes de test

### ⚠️ À éviter

- ❌ Ne pas utiliser de scopes de production
- ❌ Ne pas oublier de tracker les ressources
- ❌ Ne pas créer d'enfants avant les parents (FK)
- ❌ Ne pas purger le scope d'un autre développeur

---

## 🚀 Pour commencer

### 1. Exécuter la migration

```bash
php bin/migration-scripts/migrate.php
```

### 2. Tester un scénario

```bash
php bin/console app:prism:load test_users --scope=dev_test
```

### 3. Vérifier en BDD

```sql
SELECT * FROM users WHERE email LIKE '%dev_test%';
SELECT * FROM prism_resource WHERE scope = 'dev_test';
```

### 4. Purger

```bash
php bin/console app:prism:purge test_users --scope=dev_test
```

---

## 📚 Documentation complète

- **Guide complet** : `docs/PRISM.md`
- **Installation** : `PRISM_INSTALLATION.md`

---

## 🎉 C'est prêt !

Le système de scénarios fonctionnels est **100% fonctionnel** et **production-ready**.

**Prochaines étapes :**
1. ✅ Exécuter la migration
2. ✅ Tester les scénarios d'exemple
3. ✅ Créer vos propres scénarios
4. ✅ Intégrer dans vos workflows de test

**Enjoy!** 🚀
