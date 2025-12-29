# Installation et Configuration

> **Note** : Ce guide détaille l'installation **après** avoir ajouté le bundle via Composer.  
> Pour l'installation initiale, voir le [README principal](../README.md#-installation).

## 🚀 Post-Installation

### 1. Exécuter la migration

```bash
php bin/migration-scripts/migrate.php
```

Crée la table `prism_resource` pour le tracking.

### 2. Vérifier l'installation

```bash
php bin/console app:prism:list
```

Devrait afficher les scénarios d'exemple.

### 3. Test rapide

```bash
# Charger
php bin/console app:prism:load test_users --scope=dev_test

# Vérifier en SQL
# SELECT * FROM users WHERE email LIKE '%dev_test%';

# Purger
php bin/console app:prism:purge test_users --scope=dev_test
```

---

## 🛠️ Créer votre premier scénario

Créez `prism/scripts/MonPrismPrism.php` :

```php
<?php
namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
use Prism\Domain\ValueObject\{Scope, PrismName};

final class MonPrismPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('mon_prism');
    }
    
    public function load(Scope $scope): void
    {
        $this->insertAndTrack('users', [
            'username' => "user_{$scope}",
            'email' => "user_{$scope}@test.com",
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
    }
}
```

Testez :

```bash
php bin/console cache:clear
php bin/console app:prism:load mon_prism --scope=test
```

Pour plus d'exemples, voir [Guide PHP](PRISM_PHP.md) ou [Guide YAML](PRISM_YAML.md).

---

## 🔍 Debugging

### Voir les ressources trackées

```sql
-- Toutes les ressources d'un scope
SELECT * FROM prism_resource WHERE scope = 'dev_alice';

-- Ressources d'un scénario spécifique
SELECT * FROM prism_resource 
WHERE prism_name = 'test_users' AND scope = 'dev_alice';
```

### Logs verbeux

```bash
php bin/console app:prism:load test_users --scope=test -vvv
```

### Cache

Si vos modifications ne sont pas prises en compte :

```bash
php bin/console cache:clear
```

---

## 🎓 Pour aller plus loin

Consultez la documentation complète : `docs/PRISM.md`

### Sujets avancés :
- Gestion des relations complexes (FK)
- Conventions de nommage
- Bonnes pratiques de scope
- Scénarios avec transactions
- Testing des scénarios

---

## ✨ Avantages de cette implémentation

### 1. Table pivot (traçabilité complète)
✅ Fiabilité maximale du purge  
✅ Audit complet des ressources créées  
✅ Support des relations FK complexes  
✅ Debugging facile  

---

## 🐛 Problèmes courants

**"Table prism_resource doesn't exist"**  
→ `php bin/migration-scripts/migrate.php`

**"Scénario introuvable"**  
→ Vérifiez que le fichier se termine par `Prism.php`  
→ `php bin/console cache:clear`

**"Foreign key constraint fails"**  
→ Créez les parents avant les enfants

---

## 📚 Prochaines étapes

- [Guide de démarrage rapide](PRISM_QUICKSTART.md)
- [Documentation complète](PRISM.md)
- [Scénarios YAML](PRISM_YAML.md)
- [Scénarios PHP](PRISM_PHP.md)
