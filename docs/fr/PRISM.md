# Système de Scénarios Fonctionnels

## 📋 Vue d'ensemble

Le système de scénarios fonctionnels permet de créer des **contextes métier complets et reproductibles** pour les tests fonctionnels. Chaque scénario génère des données en base de manière contrôlée et peut être purgé proprement.

### Avantages

✅ **Déterministe** - Les données sont toujours créées de la même manière  
✅ **Isolé** - Chaque développeur peut avoir son scope sans collision  
✅ **Traçable** - Toutes les ressources créées sont trackées dans une table pivot  
✅ **Réversible** - Purge automatique en respectant les contraintes FK  
✅ **Architecture Hexagonale** - Respect des principes Domain/Application/Infrastructure  
✅ **Flexibilité** - Choisissez l'approche adaptée à votre besoin

### Types de scénarios disponibles

Le système supporte **3 approches** :

| Type | Documentation | Quand l'utiliser |
|------|---------------|------------------|
| **[YAML pur](PRISM_YAML.md)** | Guide complet YAML | Données simples, lookups, prototypage rapide |
| **[PHP pur](PRISM_PHP.md)** | Guide complet PHP | Logique complexe, calculs, boucles, conditions |
| **[Hybride](PRISM_HYBRID.md)** | Guide Hybride | Structure YAML + enrichissement PHP |

**Voir aussi** : [Guide Faker](PRISM_FAKER.md) - 44 types de données aléatoires (YAML + PHP)

---

## 📖 Commandes essentielles

### Lister les scénarios disponibles

```bash
php bin/console app:prism:list
```

Affiche tous les scénarios enregistrés (PHP, YAML, Hybrides).

### Charger un scénario

```bash
# Charger avec le scope par défaut
php bin/console app:prism:load nom_prism

# Charger avec un scope personnalisé
php bin/console app:prism:load nom_prism --scope=mon_scope
```

**Note** : Le chargement purge automatiquement les données existantes du même scope avant de créer les nouvelles.

### Purger un scénario

```bash
# Purger un scénario spécifique
php bin/console app:prism:purge nom_prism --scope=mon_scope

# Purger tous les scénarios d'un scope
php bin/console app:prism:purge --scope=mon_scope --all
```

---

## 🎯 Matrice de décision

| Critère | YAML pur | PHP pur | Hybride |
|---------|----------|---------|---------|
| **Complexité logique** | Aucune | Élevée | Moyenne |
| **Boucles/Conditions** | ❌ Non | ✅ Oui | ✅ Oui |
| **Calculs dynamiques** | ❌ Non | ✅ Oui | ✅ Oui |
| **Facilité d'écriture** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Lookups FK** | ✅ Oui | ✅ Oui | ✅ Oui |
| **Placeholders** | ✅ Oui | ❌ Manuel | ✅ Oui |
| **Prototypage rapide** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |

### Exemples de cas d'usage

#### YAML pur → [Guide complet](YAML_SCENARIOS.md)

- Créer des utilisateurs de test
- Définir des rôles/permissions (ACL)
- Charger des données de référence
- Relations simples avec lookups

#### PHP pur → [Guide complet](PRISM_PHP.md)

- Calculer des prix/remises
- Générer des statistiques
- Simuler des workflows complexes
- Logique métier avec conditions

#### Hybride → [Guide complet](PRISM_HYBRID.md)

- Données de base (YAML) + enrichissement (PHP)
- Structure simple + calculs dynamiques
- Prototypage progressif

---

## 🧪 Exemples de scénarios fournis

### TestUsersPrism (PHP pur)

```bash
php bin/console app:prism:load test_users --scope=dev_john
```

**Données :** 1 admin + 1 user

### ChatConversationPrism (PHP pur)

```bash
php bin/console app:prism:load chat_conversation --scope=dev_alice
```

**Données :** 3 utilisateurs + 5 messages avec FK

### AdvancedExamplePrism (YAML pur)

```bash
php bin/console app:prism:load advanced_example_yaml --scope=test
```

**Voir [YAML_SCENARIOS.md](YAML_SCENARIOS.md)**

### HybridExamplePrism (Hybride)

```bash
php bin/console app:prism:load hybrid_example --scope=demo
```

**Données :** Utilisateur + messages (YAML) + statistiques + notifications (PHP)

---

## 🔍 Nommage des scopes

Utilisez des préfixes clairs :

- `dev_<nom>` : Développeurs individuels (ex: `dev_alice`)
- `test_<nom>` : Tests automatisés (ex: `test_integration`)
- `qa_<nom>` : Équipe QA (ex: `qa_team_alpha`)
- `staging` : Environnement de staging
- `demo` : Démonstrations client

---

## 🔍 Table de traçabilité

La table `prism_resource` :

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT | ID auto-incrémenté |
| `prism_name` | VARCHAR(100) | Nom du scénario |
| `scope` | VARCHAR(50) | Scope d'isolation |
| `table_name` | VARCHAR(64) | Table contenant la ressource |
| `id_column_name` | VARCHAR(64) | Colonne d'ID trackée |
| `row_id` | VARCHAR(255) | ID de la ligne créée |
| `created_at` | DATETIME | Date de création |

### Requêtes utiles

```sql
-- Voir toutes les ressources d'un scope
SELECT * FROM prism_resource 
WHERE scope = 'dev_john' 
ORDER BY created_at DESC;

-- Compter par scénario
SELECT prism_name, COUNT(*) as total
FROM prism_resource 
WHERE scope = 'dev_john'
GROUP BY prism_name;
```

---

## 🛠️ Écrire un Scénario PHP

### Structure de base

Dans votre application, créez un fichier dans `src/Prism/` :

```php
<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Prism;

use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Psr\Log\LoggerInterface;

final class MonPrismPrism extends AbstractPrism
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
        return PrismName::fromString('mon_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        $this->logger->info('Chargement du scénario MonPrism', [
            'scope' => $scopeStr
        ]);
        
        // Votre logique ici...
        $this->creerUtilisateurs($scopeStr);
        
        $this->logger->info('✓ Scénario MonPrism chargé');
    }
    
    private function creerUtilisateurs(string $scope): void
    {
        // Implémentation...
    }
}
```

### Méthodes disponibles

#### `insertAndTrack()` - Insertion avec tracking automatique

```php
// Insertion simple avec tracking automatique sur la colonne 'id'
$userId = $this->insertAndTrack('users', [
    'username' => sprintf('user_%s', $scope),
    'email' => sprintf('user_%s@example.test', $scope),
    'password' => password_hash('secret', PASSWORD_BCRYPT),
], []);

// Insertion avec types Doctrine
$postId = $this->insertAndTrack('posts', [
    'title' => 'Mon article',
    'author_id' => $userId,
    'published_at' => new \DateTimeImmutable(),
], [
    'published_at' => 'datetime_immutable'
]);

// Tracking sur une colonne custom (ex: pour tables avec ID VARCHAR)
$messageId = $this->insertAndTrack('chat_messages', [
    'id' => sprintf('msg_%s_%s', $scope, uniqid()),
    'user_id' => $userId,
    'message' => 'Hello',
], [], 'user_id'); // Track par user_id au lieu de id
```

#### `getRepository()` - Accès direct au repository

```php
// Insertion manuelle (retourne int|string|null)
$id = $this->getRepository()->insert('users', [
    'username' => 'john',
    'email' => 'john@test.com'
], []);

// Puis tracking manuel
$this->trackResource('users', $id);

// Requêtes SELECT
$results = $this->getRepository()->executeQuery(
    'SELECT * FROM users WHERE username = ?',
    ['john']
);

// Requêtes UPDATE/DELETE
$affectedRows = $this->getRepository()->executeStatement(
    'UPDATE users SET active = ? WHERE id = ?',
    [true, $userId]
);

// Suppression avec conditions
$deleted = $this->getRepository()->delete('users', [
    'username' => 'john'
]);
```

#### `trackResource()` - Tracking manuel

```php
// Tracking manuel d'une ressource
$this->trackResource('ma_table', $id);

// Tracking sur colonne custom
$this->trackResource('chat_messages', $userId, 'user_id');
```

### Exemple complet : Scénario avec calculs

```php
<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scénario complexe avec calculs de tarification
 */
final class TarificationPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('tarification');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Créer un client
        $clientId = $this->creerClient($scopeStr);
        
        // Créer des produits avec tarifs calculés
        $produits = $this->creerProduits($scopeStr);
        
        // Créer une commande avec remises progressives
        $this->creerCommande($clientId, $produits, $scopeStr);
    }
    
    private function creerClient(string $scope): int
    {
        return $this->insertAndTrack('clients', [
            'nom' => sprintf('Client_%s', $scope),
            'email' => sprintf('client_%s@test.com', $scope),
            'taux_remise' => 0.15, // 15% de remise
        ], []);
    }
    
    private function creerProduits(string $scope): array
    {
        $produits = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $prixBase = 100 * $i;
            $taxe = $prixBase * 0.20; // TVA 20%
            $prixTTC = $prixBase + $taxe;
            
            $produitId = $this->insertAndTrack('produits', [
                'reference' => sprintf('PROD_%s_%03d', $scope, $i),
                'nom' => sprintf('Produit %d - %s', $i, $scope),
                'prix_ht' => $prixBase,
                'prix_ttc' => $prixTTC,
                'taux_tva' => 0.20,
            ], []);
            
            $produits[] = [
                'id' => $produitId,
                'prix_ht' => $prixBase,
                'prix_ttc' => $prixTTC,
            ];
        }
        
        return $produits;
    }
    
    private function creerCommande(int $clientId, array $produits, string $scope): void
    {
        // Récupérer le taux de remise du client
        $client = $this->getRepository()->executeQuery(
            'SELECT taux_remise FROM clients WHERE id = ?',
            [$clientId]
        );
        $tauxRemise = $client[0]['taux_remise'];
        
        // Calculer le total avec remise progressive
        $totalHT = 0;
        $totalTTC = 0;
        
        foreach ($produits as $produit) {
            // Remise progressive : +5% par produit
            $remiseSupplementaire = count($produits) * 0.05;
            $remiseTotale = min($tauxRemise + $remiseSupplementaire, 0.50); // Max 50%
            
            $prixFinalHT = $produit['prix_ht'] * (1 - $remiseTotale);
            $prixFinalTTC = $produit['prix_ttc'] * (1 - $remiseTotale);
            
            $totalHT += $prixFinalHT;
            $totalTTC += $prixFinalTTC;
        }
        
        $commandeId = $this->insertAndTrack('commandes', [
            'reference' => sprintf('CMD_%s_%s', $scope, date('YmdHis')),
            'client_id' => $clientId,
            'total_ht' => $totalHT,
            'total_ttc' => $totalTTC,
            'taux_remise_applique' => $tauxRemise,
            'statut' => 'en_attente',
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Commande créée avec calculs complexes', [
            'commande_id' => $commandeId,
            'total_ttc' => $totalTTC,
            'nb_produits' => count($produits)
        ]);
    }
}
```

---

## 🔀 Écrire un Scénario Hybride (YAML + PHP)

Les scénarios hybrides combinent la simplicité du YAML pour les données structurées avec la puissance de PHP pour la logique complexe.

### Quand utiliser un scénario hybride ?

✅ **Structure de données simple** définie en YAML  
✅ **Logique métier complexe** ajoutée en PHP  
✅ **Calculs dynamiques** sur des données de base  
✅ **Enrichissement** de données YAML  

### Structure d'un scénario hybride

```php
<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Prism;

use Prism\Application\Prism\YamlPrism;
use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Prism\Infrastructure\Yaml\YamlPrismLoader;
use Psr\Log\LoggerInterface;

/**
 * Scénario hybride : Données de base en YAML + Logique PHP
 */
final class HybridExamplePrism extends YamlPrism
{
    public function __construct(
        YamlPrismLoader $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        ?LoggerInterface $logger = null
    ) {
        // Important : passer le nom du scénario au parent
        parent::__construct(
            PrismName::fromString('hybrid_example'),
            $loader,
            $repository,
            $tracker,
            $logger
        );
    }

    /**
     * Surcharge de load() pour ajouter de la logique PHP
     */
    public function load(Scope $scope): void
    {
        // 1. Charger d'abord les données YAML
        parent::load($scope);
        
        // 2. Ajouter la logique PHP complexe
        $this->enrichirDonnees($scope);
    }
    
    private function enrichirDonnees(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Récupérer un utilisateur créé par le YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username FROM users WHERE username = ?',
            [sprintf('user_1_%s', $scopeStr)]
        );
        
        if (empty($users)) {
            $this->logger->warning('Aucun utilisateur trouvé pour enrichissement');
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Logique complexe impossible en YAML
        $this->genererStatistiques($userId, $scopeStr);
        $this->creerNotifications($userId, $scopeStr);
    }
    
    private function genererStatistiques(int $userId, string $scope): void
    {
        // Calculs complexes
        $stats = [
            'total_messages' => rand(10, 100),
            'taux_reponse' => rand(50, 95) / 100,
            'score_activite' => rand(1, 10),
        ];
        
        $this->insertAndTrack('user_statistics', [
            'user_id' => $userId,
            'total_messages' => $stats['total_messages'],
            'taux_reponse' => $stats['taux_reponse'],
            'score_activite' => $stats['score_activite'],
            'calculated_at' => new \DateTimeImmutable(),
        ], [
            'calculated_at' => 'datetime_immutable'
        ]);
    }
    
    private function creerNotifications(int $userId, string $scope): void
    {
        $types = ['info', 'warning', 'success'];
        
        foreach ($types as $type) {
            $this->insertAndTrack('notifications', [
                'user_id' => $userId,
                'type' => $type,
                'message' => sprintf('Notification %s pour %s', $type, $scope),
                'read' => false,
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ]);
        }
    }
}
```

### Fichier YAML associé

Créer `prisms/hybrid_example.yaml` :

```yaml
# prisms/hybrid_example.yaml
# Données de base chargées avant la logique PHP

load:
  # Utilisateur de base
  - table: users
    data:
      username: "user_1_{{ scope }}"
      email: "user_1_{{ scope }}@test.com"
      password: "{{ hash('password') }}"

  # Messages simples
  - table: chat_messages
    data:
      id: "msg_{{ scope }}_{{ uuid }}"
      user_id:
        table: users
        where:
          username: "user_1_{{ scope }}"
        return: id
      username: "user_1_{{ scope }}"
      message: "Message de base en YAML"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

### Ordre d'exécution

Quand vous chargez un scénario hybride :

1. **YAML** : `parent::load($scope)` charge les données du fichier YAML
2. **PHP** : Votre logique custom s'exécute après

### Exemple avancé : Workflow avec états

```php
final class WorkflowPrism extends YamlPrism
{
    public function __construct(
        YamlPrismLoader $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            PrismName::fromString('workflow'),
            $loader,
            $repository,
            $tracker,
            $logger
        );
    }

    public function load(Scope $scope): void
    {
        // Charger les données YAML (utilisateurs, projets)
        parent::load($scope);
        
        // Simuler un workflow avec transitions d'états
        $this->simulerWorkflow($scope);
    }
    
    private function simulerWorkflow(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Récupérer le projet créé en YAML
        $projets = $this->getRepository()->executeQuery(
            'SELECT id FROM projets WHERE nom = ?',
            [sprintf('Projet_%s', $scopeStr)]
        );
        
        if (empty($projets)) {
            return;
        }
        
        $projetId = $projets[0]['id'];
        
        // États du workflow
        $etats = ['draft', 'review', 'approved', 'published'];
        $dateDebut = new \DateTimeImmutable('-4 days');
        
        foreach ($etats as $index => $etat) {
            // Calculer la date de transition
            $dateTransition = $dateDebut->modify(sprintf('+%d days', $index));
            
            $this->insertAndTrack('projet_historique', [
                'projet_id' => $projetId,
                'etat' => $etat,
                'commentaire' => sprintf('Transition vers %s', $etat),
                'created_at' => $dateTransition,
            ], [
                'created_at' => 'datetime_immutable'
            ]);
        }
        
        // Mettre à jour l'état final du projet
        $this->getRepository()->executeStatement(
            'UPDATE projets SET etat = ?, updated_at = ? WHERE id = ?',
            ['published', new \DateTimeImmutable(), $projetId]
        );
    }
}
```

---

## ⚙️ Méthodes de AbstractPrism

### Résumé des méthodes disponibles

| Méthode | Description | Retour |
|---------|-------------|--------|
| `insertAndTrack($table, $data, $types, $idColumnName='id')` | Insert + tracking automatique | `int\|string` |
| `getRepository()` | Accès au repository Doctrine | `PrismDataRepositoryInterface` |
| `trackResource($table, $id, $idColumnName='id')` | Tracking manuel | `void` |
| `purge(Scope $scope)` | Purge automatique (ordre inverse) | `void` |
| `getName()` | Nom du scénario | `PrismName` |
| `load(Scope $scope)` | Charge le scénario | `void` |

### Méthode Repository

#### `insert(string $table, array $data, array $types): int|string|null`

```php
$id = $this->getRepository()->insert('users', [
    'username' => 'john',
    'email' => 'john@test.com',
], []);
```

#### `executeQuery(string $sql, array $params): array`

```php
$results = $this->getRepository()->executeQuery(
    'SELECT * FROM users WHERE username = ?',
    ['john']
);
```

#### `executeStatement(string $sql, array $params): int`

```php
$affected = $this->getRepository()->executeStatement(
    'UPDATE users SET active = ? WHERE id = ?',
    [true, $userId]
);
```

#### `delete(string $table, array $criteria): int`

```php
$deleted = $this->getRepository()->delete('users', [
    'username' => 'john'
]);
```

---

## 🎯 Quand utiliser quelle approche ?

### Matrice de décision

| Critère | YAML pur | PHP pur | Hybride |
|---------|----------|---------|---------|
| **Complexité logique** | Aucune | Élevée | Moyenne |
| **Boucles/Conditions** | ❌ Non | ✅ Oui | ✅ Oui |
| **Calculs dynamiques** | ❌ Non | ✅ Oui | ✅ Oui |
| **Facilité d'écriture** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Lookups FK** | ✅ Oui | ✅ Oui | ✅ Oui |
| **Placeholders** | ✅ Oui | ❌ Manuel | ✅ Oui |
| **Prototypage rapide** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |

### Exemples de cas d'usage

#### YAML pur ✅

- Créer des utilisateurs de test
- Définir des rôles/permissions (ACL)
- Charger des données de référence
- Créer des relations simples

```bash
# Rapide à écrire, facile à maintenir
php bin/console app:prism:load users_acl --scope=dev
```

#### PHP pur ✅

- Calculer des prix/remises
- Générer des statistiques
- Simuler des workflows complexes
- Créer des données avec logique métier

```bash
# Contrôle total, logique complexe
php bin/console app:prism:load tarification --scope=staging
```

#### Hybride ✅

- Données de base (YAML) + enrichissement (PHP)
- Structure simple + calculs dynamiques
- Prototypage avec logique métier progressive

```bash
# Meilleur des deux mondes
php bin/console app:prism:load workflow --scope=test
```

---

## 📋 Bonnes pratiques

### 1. Nommage des scénarios

```php
// ✅ Bon : descriptif et cohérent
PrismName::fromString('users_with_acl');
PrismName::fromString('chat_conversation');
PrismName::fromString('tarification_produits');

// ❌ Mauvais : trop vague
PrismName::fromString('test');
PrismName::fromString('data');
```

### 2. Utiliser insertAndTrack() par défaut

```php
// ✅ Bon : tracking automatique
$userId = $this->insertAndTrack('users', $data, []);

// ❌ Mauvais : tracking manuel inutile
$id = $this->getRepository()->insert('users', $data, []);
$this->trackResource('users', $id);
```

### 3. Logger les étapes importantes

```php
public function load(Scope $scope): void
{
    $this->logger->info('Début chargement scénario Tarification');
    
    $clientId = $this->creerClient($scope->toString());
    $this->logger->info('Client créé', ['id' => $clientId]);
    
    $this->creerProduits($scope->toString());
    $this->logger->info('Produits créés');
    
    $this->logger->info('✓ Scénario Tarification chargé');
}
```

### 4. Organiser le code en méthodes privées

```php
public function load(Scope $scope): void
{
    $scopeStr = $scope->toString();
    
    // ✅ Lisible et modulaire
    $userId = $this->creerUtilisateur($scopeStr);
    $projetId = $this->creerProjet($userId, $scopeStr);
    $this->ajouterTaches($projetId, $scopeStr);
}

private function creerUtilisateur(string $scope): int { /* ... */ }
private function creerProjet(int $userId, string $scope): int { /* ... */ }
private function ajouterTaches(int $projetId, string $scope): void { /* ... */ }
```

### 5. Gérer les types Doctrine correctement

```php
// ✅ Bon : types explicites
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

// ❌ Mauvais : types manquants
$this->insertAndTrack('events', [
    'start_date' => '2025-01-15 10:00:00', // ⚠️ String au lieu de DateTimeImmutable
    'is_active' => 1, // ⚠️ Int au lieu de bool
], []);
```

### 6. Purge automatique : ne pas surcharger sans raison

```php
// ✅ Bon : laisser la purge automatique
// AbstractPrism::purge() gère l'ordre inverse automatiquement

// ⚠️ Cas particulier seulement : purge custom
public function purge(Scope $scope): void
{
    // Logique custom AVANT la purge auto
    $this->supprimerFichiersExportés($scope);
    
    // Puis purge automatique (tracking + ordre inverse)
    parent::purge($scope);
}
```

---

## 🔗 Liens utiles

- **[Guide YAML complet](YAML_SCENARIOS.md)** - Syntaxe YAML, lookups, pivots, purge

---

## 📖 Utilisation

### Lister les scénarios disponibles

```bash
php bin/console app:prism:list
```

Affiche tous les scénarios enregistrés (PHP, YAML, Hybrides).

### Charger un scénario

```bash
# Charger avec le scope par défaut
php bin/console app:prism:load test_users

# Charger avec un scope personnalisé
php bin/console app:prism:load test_users --scope=dev_john

# Charger un scénario hybride
php bin/console app:prism:load hybrid_example --scope=staging
```

**Note** : Le chargement d'un scénario purge automatiquement les données existantes du même scope avant de créer les nouvelles.

### Purger un scénario

```bash
# Purger un scénario spécifique
php bin/console app:prism:purge test_users --scope=dev_john

# Purger tous les scénarios d'un scope
php bin/console app:prism:purge --scope=dev_john --all
```

---

## 🧪 Exemples de scénarios fournis

### TestUsersPrism (PHP pur)

Crée 2 utilisateurs simples.

```bash
php bin/console app:prism:load test_users --scope=dev_john
```

**Données créées :** 1 admin + 1 user

### ChatConversationPrism (PHP pur)

Crée 3 utilisateurs et 5 messages de chat.

```bash
php bin/console app:prism:load chat_conversation --scope=dev_alice
```

**Données créées :** 3 utilisateurs + 5 messages avec FK

### AdvancedExamplePrism (YAML pur)

Exemple complet avec lookups, pivot, purge personnalisée.

```bash
php bin/console app:prism:load advanced_example_yaml --scope=test
```

**Voir [YAML_SCENARIOS.md](YAML_SCENARIOS.md) pour les détails**

### HybridExamplePrism (Hybride)

Données YAML + enrichissement PHP.

```bash
php bin/console app:prism:load hybrid_example --scope=demo
```

**Données créées :** Utilisateur + messages (YAML) + statistiques + notifications (PHP)

---

## 🔍 Nommage des scopes

Utilisez des préfixes clairs :

- `dev_<nom>` : Développeurs individuels (ex: `dev_alice`)
- `test_<nom>` : Tests automatisés (ex: `test_integration`)
- `qa_<nom>` : Équipe QA (ex: `qa_team_alpha`)
- `staging` : Environnement de staging
- `demo` : Démonstrations client

---

## 🔍 Table de traçabilité

La table `prism_resource` :

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT | ID auto-incrémenté |
| `prism_name` | VARCHAR(100) | Nom du scénario |
| `scope` | VARCHAR(50) | Scope d'isolation |
| `table_name` | VARCHAR(64) | Table contenant la ressource |
| `id_column_name` | VARCHAR(64) | Colonne d'ID trackée |
| `row_id` | VARCHAR(255) | ID de la ligne créée |
| `created_at` | DATETIME | Date de création |

### Requêtes utiles

```sql
-- Voir toutes les ressources d'un scope
SELECT * FROM prism_resource 
WHERE scope = 'dev_john' 
ORDER BY created_at DESC;

-- Compter par scénario
SELECT prism_name, COUNT(*) as total
FROM prism_resource 
WHERE scope = 'dev_john'
GROUP BY prism_name;
```

---

## ⚙️ Configuration

Le fichier `config/services/Prism.yaml` configure :

- Les implémentations des ports du Domain
- L'injection des use cases
- L'auto-découverte des scénarios via tags
- Le logging sur le channel `prism`

---


---

## 🐛 Debugging

### Voir les logs détaillés

```bash
php bin/console app:prism:load mon_prism --scope=test -vvv
```

### Erreurs courantes

**"Scénario introuvable"**
- Vérifiez que votre classe étend `AbstractPrism` (ou `YamlPrism` pour hybrides)
- Vérifiez que le fichier se termine par `Prism.php`
- Clearez le cache : `php bin/console cache:clear`

**"Foreign key constraint fails" lors du purge**
- Le système purge en ordre inverse automatiquement
- Si vous avez ON DELETE CASCADE, vérifiez les contraintes DB

**"Lookup ne retourne aucune ligne"**
- Vérifiez que les données existent avant le lookup
- Vérifiez les conditions `where` dans votre lookup YAML
- Utilisez `-vvv` pour voir les requêtes SQL

---

## 📚 Ressources

- **[Guide PHP complet](PRISM_PHP.md)** - Créer des scénarios PHP purs
- **[Guide YAML complet](YAML_SCENARIOS.md)** - Créer des scénarios YAML purs
- **[Guide Hybride](PRISM_HYBRID.md)** - Créer des scénarios Hybrides (YAML + PHP)
- **[Architecture Hexagonale](ARCHITECTURE.md)** - Vue d'ensemble du système
- **[Permissions ACL](PERMISSIONS.md)** - Gestion des droits
- **[Documentation Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/)** - API de base de données

---

## 📝 License

Ce système fait partie du projet hexagonal-symfony sous licence propriétaire.



