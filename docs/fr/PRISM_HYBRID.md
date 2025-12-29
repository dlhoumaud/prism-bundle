# Scénarios Hybrides (YAML + PHP) - Guide Complet

## 📋 Vue d'ensemble

Les scénarios hybrides combinent **la simplicité du YAML** pour les données structurées avec **la puissance de PHP** pour la logique complexe.

### Quand utiliser un scénario hybride ?

✅ **Structure de données simple** définie en YAML  
✅ **Logique métier complexe** ajoutée en PHP  
✅ **Calculs dynamiques** sur des données de base  
✅ **Enrichissement** de données YAML  
✅ **Prototypage progressif** - Commencez en YAML, ajoutez du PHP au besoin

---

## 🚀 Démarrage rapide

### 1. Créer le fichier YAML

Créez `prism/yaml/mon_prism.yaml` :

```yaml
load:
  # Utilisateur de base
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@test.com"
      password: "{{ hash('password') }}"

  # Message simple
  - table: messages
    data:
      user_id:
        table: users
        where:
          username: "user_{{ scope }}"
        return: id
      content: "Message de base en YAML"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

### 2. Créer la classe PHP

Dans votre application, créez `prism/scripts/MonPrismPrism.php` :

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
 * Scénario hybride : Données YAML + Logique PHP
 */
final class MonPrismPrism extends AbstractPrism
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
        return PrismName::fromString('mon_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();
        
        // ======================================================================
        // ÉTAPE 1 : Charger les données YAML
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
        // ÉTAPE 2 : Ajouter la logique PHP complexe
        // ======================================================================
        
        $this->enrichirDonnees($scopeStr);
    }
    
    private function enrichirDonnees(string $scope): void
    {
        // Récupérer l'utilisateur créé par le YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('user_%s', $scope)]
        );
        
        if (empty($users)) {
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Logique complexe en PHP
        $this->genererStatistiques($userId);
    }
    
    private function genererStatistiques(int $userId): void
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

### 3. Charger le scénario

```bash
php bin/console app:prism:load mon_prism --scope=dev
```

---

### Ordre d'exécution

Quand vous chargez un scénario hybride :

1. **YAML** : `YamlPrism` charge les données du fichier YAML
   - Exécute les instructions `load:`
   - Track automatiquement les ressources créées
   
2. **PHP** : Votre logique personnalisée s'exécute
   - Accès aux données créées en YAML via `getRepository()`
   - Logique métier complexe avec `insertAndTrack()`
   - Calculs, boucles, conditions

---

## 🔑 Pivot Custom - Tracking alternatif

### Qu'est-ce que le pivot custom ?

Le **pivot custom** permet de tracker les ressources par une **colonne alternative** à `id`. Utile pour :

✅ **YAML** : Syntaxe `pivot:` dans le fichier YAML  
✅ **PHP** : 4ème paramètre de `insertAndTrack()`  
✅ **Tables avec UUID** : Track par FK INT plutôt que VARCHAR  
✅ **Purge groupée** : Supprimer toutes les ressources d'un propriétaire  

### Pivot custom en YAML

**Dans votre fichier YAML** (`prisms/mon_prism.yaml`) :

```yaml
load:
  # Créer un utilisateur
  - table: users
    data:
      username: "alice_{{ scope }}"
      email: "alice_{{ scope }}@test.com"
      password: "{{ hash('secret') }}"

  # Messages trackés par user_id
  - table: chat_messages
    data:
      id: "msg_{{ scope }}_{{ uuid }}"
      user_id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      username: "alice_{{ scope }}"
      message: "Message depuis YAML"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
    pivot:
      id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      column: user_id  # ← Track par user_id au lieu de id
```

**Résultat** : Le message est tracké avec `user_id` au lieu de son UUID.

### Pivot custom en PHP (partie hybride)

**Dans votre classe PHP** :

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
        
        // Charger le YAML (utilisateur + premier message)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Ajouter plus de messages en PHP avec pivot
        $this->ajouterMessagesSupplementaires($scopeStr);
    }
    
    private function ajouterMessagesSupplementaires(string $scope): void
    {
        // Récupérer l'utilisateur créé en YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('alice_%s', $scope)]
        );
        
        if (empty($users)) {
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Ajouter 5 messages supplémentaires avec pivot custom
        for ($i = 2; $i <= 6; $i++) {
            $this->insertAndTrack('chat_messages', [
                'id' => sprintf('msg_%s_%s_%d', $scope, uniqid(), $i),
                'user_id' => $userId,
                'username' => sprintf('alice_%s', $scope),
                'message' => sprintf('Message %d depuis PHP', $i),
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ], 'user_id'); // ← 4ème paramètre = colonne de tracking
        }
    }
}
```

### Exemple complet : Chat avec statistiques

**Fichier YAML** (`prisms/hybrid_chat_stats.yaml`) :

```yaml
load:
  # Utilisateurs
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

  # Premier message d'Alice avec pivot
  - table: chat_messages
    data:
      id: "msg_alice_{{ scope }}_{{ uuid }}"
      user_id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      username: "alice_{{ scope }}"
      message: "Salut Bob !"
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

**Classe PHP** :

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
        
        // 1. Charger YAML (utilisateurs + 1 message)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // 2. Ajouter conversation en PHP
        $this->genererConversation($scopeStr);
        
        // 3. Calculer statistiques
        $this->calculerStatistiques($scopeStr);
    }
    
    private function genererConversation(string $scope): void
    {
        // Récupérer les utilisateurs
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username FROM users WHERE username LIKE ?',
            [sprintf('%%_%s', $scope)]
        );
        
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['username']] = $user['id'];
        }
        
        // Conversation simulée
        $messages = [
            ['from' => sprintf('bob_%s', $scope), 'text' => 'Salut Alice !'],
            ['from' => sprintf('alice_%s', $scope), 'text' => 'Ça va ?'],
            ['from' => sprintf('bob_%s', $scope), 'text' => 'Très bien et toi ?'],
            ['from' => sprintf('alice_%s', $scope), 'text' => 'Super !'],
        ];
        
        foreach ($messages as $msg) {
            $userId = $userMap[$msg['from']];
            
            // Insérer avec pivot custom
            $this->insertAndTrack('chat_messages', [
                'id' => sprintf('msg_%s_%s', $scope, uniqid()),
                'user_id' => $userId,
                'username' => $msg['from'],
                'message' => $msg['text'],
                'created_at' => new \DateTimeImmutable(),
            ], [
                'created_at' => 'datetime_immutable'
            ], 'user_id'); // Pivot custom
        }
    }
    
    private function calculerStatistiques(string $scope): void
    {
        // Compter les messages par utilisateur
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

### Comparaison YAML vs PHP pour pivot

| Aspect | YAML | PHP |
|--------|------|-----|
| **Syntaxe** | Section `pivot:` dans data | 4ème param `insertAndTrack()` |
| **Lookup** | `id: {table, where, return}` | Récupération manuelle avec `executeQuery()` |
| **Simplicité** | ⭐⭐⭐⭐⭐ Plus déclaratif | ⭐⭐⭐ Plus verbeux |
| **Flexibilité** | ⭐⭐⭐ Limité au lookup | ⭐⭐⭐⭐⭐ Total (calculs, conditions) |

### Cas d'usage typique hybride

**YAML** : Structure de base avec pivot  
**PHP** : Messages supplémentaires générés dynamiquement avec pivot

**Avantages** :
- Données de base simples en YAML
- Conversation simulée en PHP (boucles, conditions)
- Tout est tracké avec pivot custom
- Purge groupée par utilisateur

### Tracking manuel avec pivot en PHP

Si vous devez tracker manuellement (insertion avec `getRepository()->insert()`), utilisez `trackResource()` avec le 3ème paramètre :

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
        
        // Charger YAML
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Ajout avec tracking manuel
        $this->ajouterMessagesManuel($scopeStr);
    }
    
    private function ajouterMessagesManuel(string $scope): void
    {
        // Récupérer l'utilisateur créé en YAML
        $users = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('alice_%s', $scope)]
        );
        
        if (empty($users)) {
            return;
        }
        
        $userId = $users[0]['id'];
        
        // Insertion manuelle (sans insertAndTrack)
        $messageId = $this->getRepository()->insert('chat_messages', [
            'id' => sprintf('msg_manual_%s_%s', $scope, uniqid()),
            'user_id' => $userId,
            'username' => sprintf('alice_%s', $scope),
            'message' => 'Message avec tracking manuel',
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        // Tracking manuel avec pivot custom
        $this->trackResource('chat_messages', $userId, 'user_id');
        //                                      ↑         ↑
        //                                      |         Colonne de tracking
        //                                      Valeur à tracker
    }
}
```

**Pourquoi tracking manuel ?**

- ✅ Besoin de l'ID retourné avant d'autres opérations
- ✅ Insertion conditionnelle complexe
- ✅ Requêtes SQL custom avec `executeStatement()`
- ✅ Batch inserts optimisés

**Comparaison** :

| Méthode | Syntaxe | Cas d'usage |
|---------|---------|-------------|
| `insertAndTrack()` | `insertAndTrack($table, $data, $types, 'user_id')` | ✅ Standard, simple |
| `insert()` + `trackResource()` | `insert(...)`<br/>`trackResource($table, $id, 'user_id')` | ⚙️ Contrôle fin, SQL custom |

---

## 📚 Exemples complets

### Exemple 1 : Enrichissement avec statistiques

**Fichier YAML** (`prisms/hybrid_stats.yaml`) :

```yaml
load:
  # Créer 3 utilisateurs
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

  # Messages de base
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

**Classe PHP** :

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
        
        // Charger les données YAML (utilisateurs + messages)
        $yamlPrism = new YamlPrism(
            $this->getName(),
            $this->loader,
            $this->getRepository(),
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver
        );
        
        $yamlPrism->load($scope);
        
        // Enrichir avec des statistiques calculées
        $this->genererStatistiques($scopeStr);
    }
    
    private function genererStatistiques(string $scope): void
    {
        // Récupérer tous les utilisateurs créés
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username FROM users WHERE username LIKE ?',
            [sprintf('%%_%s', $scope)]
        );
        
        foreach ($users as $user) {
            // Compter les messages de l'utilisateur
            $messages = $this->getRepository()->executeQuery(
                'SELECT COUNT(*) as count FROM messages WHERE user_id = ?',
                [$user['id']]
            );
            
            $messageCount = (int) $messages[0]['count'];
            
            // Calculer un score d'activité
            $score = $this->calculerScore($messageCount);
            
            // Persister les statistiques
            $this->insertAndTrack('user_statistics', [
                'user_id' => $user['id'],
                'total_messages' => $messageCount,
                'score_activite' => $score,
                'niveau' => $this->determinerNiveau($score),
                'calculated_at' => new \DateTimeImmutable(),
            ], [
                'calculated_at' => 'datetime_immutable'
            ]);
        }
    }
    
    private function calculerScore(int $messageCount): int
    {
        // Logique de calcul complexe
        $baseScore = $messageCount * 10;
        $bonus = $messageCount > 10 ? 50 : 0;
        return $baseScore + $bonus;
    }
    
    private function determinerNiveau(int $score): string
    {
        return match (true) {
            $score >= 100 => 'expert',
            $score >= 50 => 'avance',
            $score >= 20 => 'intermediaire',
            default => 'debutant',
        };
    }
}
```

### Exemple 2 : Workflow avec transitions d'états

**Fichier YAML** (`prisms/hybrid_workflow.yaml`) :

```yaml
load:
  # Utilisateur
  - table: users
    data:
      username: "chef_{{ scope }}"
      email: "chef_{{ scope }}@test.com"
      password: "{{ hash('password') }}"

  # Projet initial
  - table: projets
    data:
      nom: "Projet_{{ scope }}"
      chef_id:
        table: users
        where:
          username: "chef_{{ scope }}"
        return: id
      etat: "draft"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

**Classe PHP** :

```php
<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Prism;

use Prism\Application\Prism\YamlPrism;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class HybridWorkflowPrism extends YamlPrism
{
    public function __construct(
        YamlPrismLoader $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            PrismName::fromString('hybrid_workflow'),
            $loader,
            $repository,
            $tracker,
            $logger
        );
    }

    public function load(Scope $scope): void
    {
        // Charger les données YAML (utilisateur + projet)
        parent::load($scope);
        
        // Simuler le workflow
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
        $etats = [
            ['etat' => 'draft', 'delai' => 0],
            ['etat' => 'review', 'delai' => 1],
            ['etat' => 'approved', 'delai' => 2],
            ['etat' => 'published', 'delai' => 3],
        ];
        
        $dateDebut = new \DateTimeImmutable('-3 days');
        
        foreach ($etats as $transition) {
            $dateTransition = $dateDebut->modify(sprintf('+%d days', $transition['delai']));
            
            // Créer l'historique
            $this->insertAndTrack('projet_historique', [
                'projet_id' => $projetId,
                'etat' => $transition['etat'],
                'commentaire' => $this->genererCommentaire($transition['etat']),
                'created_at' => $dateTransition,
            ], [
                'created_at' => 'datetime_immutable'
            ]);
        }
        
        // Mettre à jour l'état final
        $this->getRepository()->executeStatement(
            'UPDATE projets SET etat = ?, updated_at = ? WHERE id = ?',
            ['published', new \DateTimeImmutable(), $projetId]
        );
        
        $this->logger->info('Workflow simulé', [
            'projet_id' => $projetId,
            'nb_transitions' => count($etats)
        ]);
    }
    
    private function genererCommentaire(string $etat): string
    {
        $commentaires = [
            'draft' => 'Projet créé en brouillon',
            'review' => 'Projet envoyé en révision',
            'approved' => 'Projet approuvé par le comité',
            'published' => 'Projet publié et accessible',
        ];
        
        return $commentaires[$etat] ?? sprintf('Transition vers %s', $etat);
    }
}
```

### Exemple 3 : Notifications dynamiques

**Fichier YAML** (`prisms/hybrid_notifications.yaml`) :

```yaml
load:
  # Créer des utilisateurs
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

**Classe PHP** :

```php
<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Prism;

use Prism\Application\Prism\YamlPrism;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

final class HybridNotificationsPrism extends YamlPrism
{
    public function __construct(
        YamlPrismLoader $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            PrismName::fromString('hybrid_notifications'),
            $loader,
            $repository,
            $tracker,
            $logger
        );
    }

    public function load(Scope $scope): void
    {
        parent::load($scope);
        $this->genererNotifications($scope);
    }
    
    private function genererNotifications(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Récupérer tous les utilisateurs
        $users = $this->getRepository()->executeQuery(
            'SELECT id, username, role FROM users WHERE username LIKE ?',
            [sprintf('%%_%s', $scopeStr)]
        );
        
        foreach ($users as $user) {
            // Notifications spécifiques au rôle
            $notifications = $this->getNotificationsPourRole($user['role']);
            
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
            
            $this->logger->debug('Notifications créées', [
                'username' => $user['username'],
                'count' => count($notifications)
            ]);
        }
    }
    
    private function getNotificationsPourRole(string $role): array
    {
        $notificationsBase = [
            ['type' => 'info', 'message' => 'Bienvenue %s !', 'priority' => 1],
            ['type' => 'success', 'message' => 'Compte activé pour %s', 'priority' => 2],
        ];
        
        if ($role === 'admin') {
            $notificationsBase[] = ['type' => 'warning', 'message' => '%s : pensez à configurer les paramètres', 'priority' => 3];
            $notificationsBase[] = ['type' => 'info', 'message' => '%s a accès aux outils d\'administration', 'priority' => 1];
        }
        
        return $notificationsBase;
    }
}
```

---

## 🎲 Génération de données aléatoires (fake)

Les scénarios hybrides peuvent utiliser `{{ fake() }}` à la fois en **YAML** et en **PHP** pour générer des données aléatoires.

### Fake en YAML

Utilisez les placeholders `{{ fake() }}` directement dans vos fichiers YAML :

```yaml
# prisms/hybrid_users_fake.yaml
load:
  # Utilisateur avec fake en YAML
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

  # Profil utilisateur
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

### Fake en PHP (partie hybride)

Utilisez la méthode `fake()` dans votre classe PHP :

```php
final class HybridUsersFakePrism extends YamlPrism
{
    public function load(Scope $scope): void
    {
        // 1. Charger utilisateur de base depuis YAML
        parent::load($scope);
        
        // 2. Ajouter plus d'utilisateurs avec fake() en PHP
        $this->creerUtilisateursSupplementaires();
    }
    
    private function creerUtilisateursSupplementaires(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            // Générer données avec fake()
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
            
            // Profil avec fake()
            $this->insertAndTrack('user_profiles', [
                'user_id' => $userId,
                'bio' => $this->fake('text', 200),
                'website' => $this->fake('url', 'https'),
                'company' => $this->fake('company'),
                'favorite_color' => $this->fake('color'),
            ], []);
        }
        
        $this->logger->info('✓ 10 utilisateurs supplémentaires créés avec fake()');
    }
}
```

### Exemple : Articles de blog avec fake

**YAML** - Structure de base :

```yaml
# prisms/hybrid_blog_fake.yaml
load:
  # Auteur principal
  - table: users
    data:
      username: "author_{{ scope }}"
      email: "author_{{ scope }}@blog.test"
      firstname: "{{ fake(firstname) }}"
      lastname: "{{ fake(lastname) }}"
      password: "{{ hash('password') }}"

  # Premier article
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

**PHP** - Génération en masse :

```php
final class HybridBlogFakePrism extends YamlPrism
{
    public function load(Scope $scope): void
    {
        // Charger auteur + premier article
        parent::load($scope);
        
        // Générer 50 articles supplémentaires
        $this->genererArticles($scope);
        
        // Ajouter commentaires aléatoires
        $this->genererCommentaires();
    }
    
    private function genererArticles(Scope $scope): void
    {
        $scopeStr = $scope->toString();
        
        // Récupérer l'auteur
        $author = $this->getRepository()->executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [sprintf('author_%s', $scopeStr)]
        );
        
        $authorId = $author[0]['id'];
        
        // Générer 50 articles
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
        
        $this->logger->info('✓ 50 articles générés avec fake()');
    }
    
    private function genererCommentaires(): void
    {
        // Récupérer tous les posts
        $posts = $this->getRepository()->executeQuery(
            'SELECT id FROM posts ORDER BY id'
        );
        
        // 3-7 commentaires par article
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
        
        $this->logger->info('✓ Commentaires générés avec fake()');
    }
}
```

### Combinaison fake + variables

Vous pouvez combiner `fake()` avec des variables pour réutiliser certaines valeurs :

```yaml
# YAML
vars:
  # Valeur fixe
  admin_email: "admin_{{ scope }}@test.com"
  
  # Valeur fake stockée dans variable
  random_company: "{{ fake(company) }}"

load:
  - table: users
    data:
      email: "{{ $admin_email }}"           # Valeur fixe
      company: "{{ $random_company }}"      # Même fake partout
      phone: "{{ fake(tel) }}"               # Nouveau fake à chaque fois
```

```php
// PHP
public function load(Scope $scope): void
{
    parent::load($scope);
    
    // Stocker une valeur fake pour réutilisation
    $companyName = $this->fake('company');
    
    for ($i = 1; $i <= 5; $i++) {
        $this->insertAndTrack('employees', [
            'name' => $this->fake('firstname') . ' ' . $this->fake('lastname'),
            'email' => $this->fake('email', 'corp.com'),
            'company' => $companyName, // ✅ Même entreprise pour tous
        ], []);
    }
}
```

### Types fake disponibles

Tous les types `fake()` sont disponibles en **YAML** et **PHP**. Voir la documentation complète :

- **[Guide YAML - Section fake()](PRISM_YAML.md#fake)** - Syntaxe YAML
- **[Guide PHP - Section fake()](PRISM_PHP.md#génération-de-données-aléatoires)** - API PHP

**Types principaux** :
- Identité : `user`, `email`, `firstname`, `lastname`, `company`
- IDs : `id`, `uuid`
- Dates : `date`, `datetime`
- Fichiers : `pathfile`, `pathdir`
- Texte : `text`, `number`
- Réseau : `url`, `ip`, `ipv6`, `mac`, `tel`
- Divers : `color`, `boolean`

---

## 📋 Bonnes pratiques

### 1. Gardez le YAML simple

```yaml
# ✅ Bon : structure claire en YAML
load:
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@test.com"

# ❌ Mauvais : logique complexe en YAML (impossible)
# Utilisez PHP pour les calculs
```

### 2. Utilisez PHP pour la logique métier

```php
// ✅ Bon : calculs en PHP
private function calculerScore(int $actions): int
{
    return $actions > 10 ? $actions * 2 : $actions;
}

// ❌ Mauvais : essayer de faire des calculs en YAML
```

### 3. Récupérez les données YAML avant d'enrichir

```php
public function load(Scope $scope): void
{
    // ✅ Bon : charger YAML d'abord
    parent::load($scope);
    
    // Puis enrichir
    $this->enrichirDonnees($scope);
}

// ❌ Mauvais : oublier d'appeler parent::load()
```

### 4. Loggez les enrichissements

```php
private function genererStatistiques(Scope $scope): void
{
    // ... logique ...
    
    $this->logger->info('Statistiques générées', [
        'scope' => $scope->toString(),
        'count' => $statsCount
    ]);
}
```

### 5. Gérez les cas où les données YAML n'existent pas

```php
private function enrichirDonnees(Scope $scope): void
{
    $users = $this->getRepository()->executeQuery(...);
    
    // ✅ Bon : vérifier avant de continuer
    if (empty($users)) {
        $this->logger->warning('Aucun utilisateur trouvé pour enrichissement');
        return;
    }
    
    // Logique d'enrichissement...
}
```

---

## 🎯 Quand utiliser un scénario hybride ?

### ✅ Cas d'usage idéaux

- **Prototypage progressif** : Commencez avec du YAML, ajoutez du PHP au fur et à mesure
- **Données de référence + calculs** : Structure de base en YAML, enrichissement calculé en PHP
- **Workflows simples** : État initial en YAML, transitions en PHP
- **Tests avec variations** : Données fixes en YAML, variations dynamiques en PHP

### ❌ Évitez les hybrides quand...

- **Tout est simple** → Utilisez YAML pur ([Guide YAML](YAML_SCENARIOS.md))
- **Tout est complexe** → Utilisez PHP pur ([Guide PHP](PRISM_PHP.md))
- **Pas de logique métier** → YAML suffit
- **Aucune structure répétitive** → PHP pur plus clair

---

## 🔗 Ressources

- **[Documentation principale](PRISM.md)** - Vue d'ensemble du système
- **[Guide YAML](PRISM_YAML.md)** - Syntaxe YAML complète
- **[Guide PHP](PRISM_PHP.md)** - API PHP complète
