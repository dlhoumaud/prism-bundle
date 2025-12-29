# Scénarios PHP - Guide Complet

## 📋 Vue d'ensemble

Les scénarios PHP permettent de créer des **contextes de test avec logique métier complexe**. Utilisez cette approche quand vous avez besoin de :

✅ **Calculs dynamiques** - Prix, remises, statistiques  
✅ **Boucles et conditions** - Logique conditionnelle complexe  
✅ **Workflows** - Transitions d'états, processus métier  
✅ **Contrôle total** - Accès complet à l'API PHP  

---

## 🚀 Démarrage rapide

### Structure de base

Dans votre application, créez un fichier dans `prism/scripts/` :

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

final class MonPrismPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('mon_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Votre logique ici...
        $this->creerUtilisateurs($scopeStr);
    }
    
    private function creerUtilisateurs(string $scope): void
    {
        // Implémentation...
    }
}
```

### Charger le scénario

```bash
php bin/console app:prism:load mon_prism --scope=dev
```

---

## 🛠️ API Disponible

### `insertAndTrack()` - Insertion avec tracking automatique

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

// Insertion dans une base de données secondaire
$logId = $this->insertAndTrack('audit_logs', [
    'user_id' => $userId,
    'action' => 'user_created',
    'created_at' => new \DateTimeImmutable(),
], [
    'created_at' => 'datetime_immutable'
], 'id', 'hexagonal_secondary');
```

### `getRepository()` - Accès direct au repository

```php
// Insertion manuelle (retourne int|string|null)
$id = $this->getRepository()->insert('users', [
    'username' => 'john',
    'email' => 'john@test.com'
], []);

// Puis tracking manuel si nécessaire
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

**Syntaxe complète** :
```php
protected function trackResource(
    string $tableName,
    int|string $rowId,
    string $idColumnName = 'id',
    ?string $dbName = null
): void
```

**Utilisation** :

```php
// Tracking standard (colonne 'id')
$this->trackResource('users', $userId);

// Tracking avec pivot custom (colonne alternative)
$this->trackResource('chat_messages', $userId, 'user_id');
$this->trackResource('orders', $customerId, 'customer_id');
$this->trackResource('logs', $sessionId, 'session_id');

// Tracking dans une base de données secondaire
$this->trackResource('audit_logs', $userId, 'id', 'hexagonal_secondary');
```

**Cas d'usage** : Quand vous insérez manuellement avec `getRepository()->insert()` au lieu de `insertAndTrack()`.

**Exemple avec insertion manuelle** :

```php
public function load(Scope $scope): void
{
    $scopeStr = $scope->toString();
    
    // Créer un utilisateur
    $userId = $this->insertAndTrack('users', [
        'username' => sprintf('alice_%s', $scopeStr),
        'email' => sprintf('alice_%s@test.com', $scopeStr),
        'password' => password_hash('secret', PASSWORD_BCRYPT),
    ], []);
    
    // Insertion manuelle d'un message
    $messageId = $this->getRepository()->insert('chat_messages', [
        'id' => sprintf('msg_%s_%s', $scopeStr, uniqid()),
        'user_id' => $userId,
        'message' => 'Hello',
        'created_at' => new \DateTimeImmutable(),
    ], [
        'created_at' => 'datetime_immutable'
    ]);
    
    // Tracking manuel avec pivot custom
    $this->trackResource('chat_messages', $userId, 'user_id');
    // ☝️ Track par user_id au lieu de l'UUID message_id
}
```

**Pourquoi tracking manuel ?**

- ✅ Insertion avec `executeStatement()` (requêtes SQL custom)
- ✅ Besoin de l'ID avant le tracking
- ✅ Logique conditionnelle complexe
- ✅ Batch inserts

**Comparaison** :

```php
// ❌ Moins pratique : insertion + tracking manuel
$id = $this->getRepository()->insert('users', $data, []);
$this->trackResource('users', $id);

// ✅ Plus simple : insertAndTrack fait les deux
$id = $this->insertAndTrack('users', $data, []);

// ✅ Avec pivot : insertAndTrack gère tout
$id = $this->insertAndTrack('chat_messages', $data, [], 'user_id');

// ✅ Mais parfois nécessaire pour SQL custom
$this->getRepository()->executeStatement(
    'INSERT INTO logs (user_id, action) SELECT id, "created" FROM users WHERE scope = ?',
    [$scope]
);
// Puis tracking manuel par user_id
$this->trackResource('logs', $userId, 'user_id');
```

---

## 🔑 Pivot Custom - Tracking alternatif

### Qu'est-ce que le pivot custom ?

Par défaut, le système track les ressources par leur colonne `id`. Le **pivot custom** permet de tracker par une **autre colonne**, utile pour :

✅ **Tables avec ID VARCHAR** - Track par une FK INT plutôt que l'UUID  
✅ **Relations multiples** - Track par l'utilisateur propriétaire plutôt que l'ID de la ressource  
✅ **Purge groupée** - Supprimer toutes les ressources d'un utilisateur en une fois  

### Cas d'usage typique : Messages avec UUID

**Problème** : Table `chat_messages` avec `id` VARCHAR (UUID) mais vous voulez supprimer tous les messages d'un utilisateur.

```php
// ❌ Sans pivot : track par l'UUID
$messageId = $this->insertAndTrack('chat_messages', [
    'id' => 'msg_' . uniqid(),
    'user_id' => 42,
    'message' => 'Hello'
], []);
// Track: id = 'msg_123abc' → Purge message par message

// ✅ Avec pivot : track par user_id
$messageId = $this->insertAndTrack('chat_messages', [
    'id' => 'msg_' . uniqid(),
    'user_id' => 42,
    'message' => 'Hello'
], [], 'user_id');
// Track: user_id = 42 → Purge tous les messages de l'utilisateur
```

### Syntaxe avec `insertAndTrack()`

```php
public function insertAndTrack(
    string $table,
    array $data,
    array $types = [],
    string $idColumnName = 'id'  // ← Colonne de tracking
): int|string
```

**4ème paramètre** : Nom de la colonne à tracker (défaut: `'id'`)

### Exemple complet : Messages de chat

```php
final class ChatPrism extends AbstractPrism
{
    public function load(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Créer un utilisateur
        $userId = $this->insertAndTrack('users', [
            'username' => sprintf('alice_%s', $scopeStr),
            'email' => sprintf('alice_%s@test.com', $scopeStr),
            'password' => password_hash('secret', PASSWORD_BCRYPT),
        ], []);
        
        // Créer plusieurs messages trackés par user_id
        for ($i = 1; $i <= 5; $i++) {
            $this->insertAndTrack('chat_messages', [
                'id' => sprintf('msg_%s_%s_%d', $scopeStr, uniqid(), $i),
                'user_id' => $userId,
                'username' => sprintf('alice_%s', $scopeStr),
                'message' => sprintf('Message %d', $i),
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ], 'user_id'); // ← Track par user_id au lieu de id
        }
        
        $this->logger->info('Messages créés avec pivot custom', [
            'user_id' => $userId,
            'count' => 5
        ]);
    }
}
```

**Au purge** : Supprime automatiquement tous les messages où `user_id = $userId`

### Exemple avec lookup dynamique

```php
private function creerMessagesAvecPivot(string $scope): void
{
    // Récupérer l'utilisateur existant
    $users = $this->getRepository()->executeQuery(
        'SELECT id FROM users WHERE username = ?',
        [sprintf('alice_%s', $scope)]
    );
    
    if (empty($users)) {
        return;
    }
    
    $userId = $users[0]['id'];
    
    // Messages trackés par user_id
    $messages = [
        'Bonjour tout le monde !',
        'Comment allez-vous ?',
        'Je suis disponible pour discuter.',
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
        ], 'user_id'); // Pivot custom
    }
}
```

### Comparaison tracking standard vs pivot

| Aspect | Tracking standard (`id`) | Pivot custom (`user_id`) |
|--------|--------------------------|-------------------------|
| **Colonne trackée** | `id` (VARCHAR UUID) | `user_id` (INT FK) |
| **Purge** | Message par message | Tous les messages d'un utilisateur |
| **Performance** | Plus lent (1 DELETE par message) | Rapide (1 DELETE avec WHERE) |
| **Cas d'usage** | Tables classiques avec ID INT | Tables avec UUID + FK importante |

### Quand utiliser le pivot custom ?

✅ **Utilisez pivot custom quand** :  
- Votre table a un `id` VARCHAR (UUID)
- Vous voulez purger par relation (tous les messages d'un user)
- Vous avez plusieurs ressources liées à une entité principale

❌ **N'utilisez pas pivot custom quand** :  
- Votre table a un `id` INT auto-incrémenté standard
- Chaque ressource doit être purgée individuellement
- Pas de FK logique pour grouper

### Pivot custom avec tracking manuel

Si vous utilisez `getRepository()->insert()` au lieu de `insertAndTrack()`, vous pouvez tracker manuellement avec pivot :

```php
private function creerMessagesBatch(int $userId, string $scope): void
{
    $messages = [
        'Bonjour !',
        'Comment allez-vous ?',
        'À bientôt !',
    ];
    
    foreach ($messages as $content) {
        // Insertion manuelle
        $messageId = $this->getRepository()->insert('chat_messages', [
            'id' => sprintf('msg_%s_%s', $scope, uniqid()),
            'user_id' => $userId,
            'message' => $content,
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        // Tracking manuel avec pivot custom
        // ☝️ Ne pas tracker par messageId (VARCHAR), mais par userId (INT)
        $this->trackResource('chat_messages', $userId, 'user_id');
    }
    
    $this->logger->info('Batch messages créés avec pivot', [
        'user_id' => $userId,
        'count' => count($messages)
    ]);
}
```

**Résultat** : Les 3 messages sont trackés avec `user_id`, donc le purge supprimera tous les messages de cet utilisateur en une requête.

### Méthodes Repository disponibles

| Méthode | Description | Retour |
|---------|-------------|--------|
| `insert($table, $data, $types)` | Insertion dans une table | `int\|string\|null` |
| `executeQuery($sql, $params)` | Requête SELECT | `array` |
| `executeStatement($sql, $params)` | UPDATE/DELETE | `int` (lignes affectées) |
| `delete($table, $criteria)` | Suppression avec critères | `int` (lignes supprimées) |

---

## 🎲 Génération de données aléatoires

### `fake(type, ...params)` - Helper pour fausses données

Génère des données aléatoires pour les tests.

```php
$this->fake('user')                // Nom d'utilisateur
$this->fake('email', 'acme.com')   // Email avec domaine
$this->fake('iban', 'DE')          // IBAN allemand
$this->fake('date', 'd/m/Y')       // Date formatée
```

**📚 44 types disponibles** : Voir [Guide Faker complet](PRISM_FAKER.md)

---

## 📚 Exemples complets

### Exemple 1 : Scénario avec calculs de tarification

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scénario avec calculs de tarification complexes
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

### Exemple 2 : Workflow avec transitions d'états

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scénario simulant un workflow avec états
 */
final class WorkflowProjetPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('workflow_projet');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Créer un utilisateur
        $userId = $this->creerUtilisateur($scopeStr);
        
        // Créer un projet
        $projetId = $this->creerProjet($userId, $scopeStr);
        
        // Simuler le workflow
        $this->simulerTransitions($projetId, $scopeStr);
    }
    
    private function creerUtilisateur(string $scope): int
    {
        return $this->insertAndTrack('users', [
            'username' => sprintf('chef_projet_%s', $scope),
            'email' => sprintf('chef_%s@test.com', $scope),
            'password' => password_hash('secret', PASSWORD_BCRYPT),
        ], []);
    }
    
    private function creerProjet(int $userId, string $scope): int
    {
        return $this->insertAndTrack('projets', [
            'nom' => sprintf('Projet_%s', $scope),
            'chef_id' => $userId,
            'etat' => 'draft',
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
    }
    
    private function simulerTransitions(int $projetId, string $scope): void
    {
        $etats = ['draft', 'review', 'approved', 'published'];
        $dateDebut = new \DateTimeImmutable('-4 days');
        
        foreach ($etats as $index => $etat) {
            // Calculer la date de transition
            $dateTransition = $dateDebut->modify(sprintf('+%d days', $index));
            
            // Créer l'historique de transition
            $this->insertAndTrack('projet_historique', [
                'projet_id' => $projetId,
                'etat' => $etat,
                'commentaire' => sprintf('Transition vers %s', $etat),
                'created_at' => $dateTransition,
            ], [
                'created_at' => 'datetime_immutable'
            ]);
            
            $this->logger->debug('Transition enregistrée', [
                'etat' => $etat,
                'date' => $dateTransition->format('Y-m-d H:i:s')
            ]);
        }
        
        // Mettre à jour l'état final du projet
        $this->getRepository()->executeStatement(
            'UPDATE projets SET etat = ?, updated_at = ? WHERE id = ?',
            ['published', new \DateTimeImmutable(), $projetId]
        );
        
        $this->logger->info('Workflow terminé', ['projet_id' => $projetId]);
    }
}
```

### Exemple 3 : Statistiques et agrégations

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Scénario générant des statistiques utilisateurs
 */
final class StatistiquesPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('statistiques');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // Créer des utilisateurs
        $userIds = $this->creerUtilisateurs($scopeStr);
        
        // Créer des actions pour chaque utilisateur
        foreach ($userIds as $userId) {
            $this->creerActions($userId, $scopeStr);
        }
        
        // Calculer et persister les statistiques
        foreach ($userIds as $userId) {
            $this->calculerStatistiques($userId, $scopeStr);
        }
    }
    
    private function creerUtilisateurs(string $scope): array
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
    
    private function creerActions(int $userId, string $scope): void
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
    
    private function calculerStatistiques(int $userId, string $scope): void
    {
        // Récupérer les actions de l'utilisateur
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
        
        // Calculer le score d'activité
        $score = ($stats['views'] * 1) + ($stats['clicks'] * 2) + ($stats['downloads'] * 5);
        
        // Persister les statistiques
        $this->insertAndTrack('user_statistics', [
            'user_id' => $userId,
            'total_views' => $stats['views'],
            'total_clicks' => $stats['clicks'],
            'total_downloads' => $stats['downloads'],
            'score_activite' => $score,
            'calculated_at' => new \DateTimeImmutable(),
        ], [
            'calculated_at' => 'datetime_immutable'
        ]);
        
        $this->logger->debug('Statistiques calculées', [
            'user_id' => $userId,
            'score' => $score
        ]);
    }
}
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

## 🔗 Ressources

- **[Documentation principale](PRISM.md)** - Vue d'ensemble du système
- **[Guide YAML](SCENARIOS_YAML.md)** - Créer des scénarios YAML
- **[Guide Hybride](PRISM_HYBRID.md)** - Combiner YAML et PHP