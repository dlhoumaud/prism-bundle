# ⚡ Quick Start - Scénarios Fonctionnels

## 🚀 Installation (2 minutes)

```bash
# 1. Exécuter la migration
php bin/migration-scripts/migrate.php

# 2. Vérifier l'installation
php bin/console app:prism:list
```

---

## 🎮 Utilisation

### Charger un scénario
```bash
php bin/console app:prism:load test_users --scope=dev_<votre_nom>
```

### Purger un scénario
```bash
php bin/console app:prism:purge test_users --scope=dev_<votre_nom>
```

### Lister les scénarios
```bash
php bin/console app:prism:list
```

---

## 📝 Créer votre scénario (1 minute)

**Fichier :** `App\Prism\MonPrismPrism.php`

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
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
        // Insérer et tracker automatiquement
        $id = $this->insertAndTrack('ma_table', [
            'field' => sprintf('value_%s', $scope->toString()),
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Créé: {id}', ['id' => $id]);
    }
    
    // purge() est déjà implémenté automatiquement ✅
}
```

**Test :**
```bash
php bin/console cache:clear
php bin/console app:prism:load mon_prism --scope=test
```

---

## 📚 Documentation

- **Guide complet** : [`docs/PRISM.md`](docs/PRISM.md)
- **Installation** : [`PRISM_INSTALLATION.md`](PRISM_INSTALLATION.md)
- **Vue d'ensemble** : [`PRISM_OVERVIEW.md`](PRISM_OVERVIEW.md)

---

## 🎯 3 Scénarios d'exemple fournis

| Scénario | Description | Complexité |
|----------|-------------|------------|
| `test_users` | 2 utilisateurs simples | 🟢 Simple |
| `chat_conversation` | 3 users + 5 messages | 🟡 Moyen |
| `advanced_example` | 5 users + messages + ACL | 🔴 Avancé |

---

## ✨ Avantages

✅ **Isolé** - Scopes personnels, pas de collision  
✅ **Fiable** - Table pivot, purge automatique  
✅ **Simple** - API `insertAndTrack()`, auto-découverte  
✅ **Testable** - Architecture propre et modulaire  

---

## 🎉 C'est prêt !

**Commencez maintenant :**

```bash
# 1. Migration
php bin/migration-scripts/migrate.php

# 2. Test
php bin/console app:prism:load test_users --scope=dev_test

# 3. Vérif BDD
# SELECT * FROM users WHERE email LIKE '%dev_test%';

# 4. Purge
php bin/console app:prism:purge test_users --scope=dev_test
```

**Enjoy!** 🚀
