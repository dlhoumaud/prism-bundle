# PrismBundle

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E6.0%7C%5E7.0-green.svg)](https://symfony.com/)
[![Tests](https://img.shields.io/badge/tests-399%20passed-brightgreen.svg)](https://github.com/dlhoumaud/prism-bundle)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/dlhoumaud/prism-bundle)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR12-0%20error-brightgreen.svg)](https://phpstan.org/)

Bundle Symfony pour la gestion de **scénarios fonctionnels** avec isolation multi-scope, traçabilité complète et purge intelligent.

## 🎯 Qu'est-ce que c'est ?

Un **système d'orchestration de contextes métier** permettant à chaque développeur de créer des univers de données isolés, reproductibles et destructibles sans collision.

### Innovations Majeures

✅ **Isolation Multi-Scope** : Plusieurs développeurs travaillent sur la même base sans collision  
✅ **Traçabilité Totale** : Table pivot trackant chaque donnée créée  
✅ **Purge Intelligent** : Suppression en ordre inverse respectant les FK  
✅ **Pivot Custom** : Track par n'importe quelle colonne (pas seulement `id`)  
✅ **Scénarios Hybrides** : Combinez YAML (déclaratif) + PHP (impératif)  
✅ **Imports Modulaires** : Composez des scénarios complexes à partir de modules  
✅ **Architecture Hexagonale** : Domain/Application/Infrastructure  


## 📦 Installation

### Option 1: Installation via Git Repository (recommandé)

Une fois le bundle publié sur GitHub, ajoutez le repository VCS dans `composer.json` :

**Étape 1 : Configurer le repository Git dans `composer.json`**

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/dlhoumaud/prism-bundle.git"
        }
    ],
    "require": {
        "prism/bundle": "dev-main"
    }
}
```

**Étape 2 : Installer le bundle**

```bash
composer require prism/bundle:dev-main
```

> 💡 **Astuce** : Une fois des versions taggées (v1.0.0, v1.1.0, etc.), vous pourrez utiliser :
> ```bash
> composer require prism/bundle:^1.0
> ```

**Étape 3 : Configuration automatique**

Symfony Flex configurera automatiquement :
- `config/bundles.php` : Ajout de `Prism\PrismBundle::class`
- `config/packages/prism.yaml` : Configuration par défaut

**Étape 4 : Créer le dossier prism**

```bash
mkdir prism
```

**Étape 5 : Vérifier l'installation**

```bash
php bin/console app:prism:list
```

---

### Option 2: Installation via Path Repository (développement local)

**Étape 1 : Copier la recette locale** (pour auto-configuration)

```bash
cp -r PrismBundle/recipes/prism-bundle config/recipes/
```

**Étape 2 : Ajouter le repository dans `composer.json`**

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./PrismBundle"
        }
    ],
    "require": {
        "prism/bundle": "@dev"
    }
}
```

**Étape 3 : Installer le bundle**

```bash
composer update prism/bundle
```

> ℹ️ Symfony Flex ajoutera automatiquement `Prism\PrismBundle::class` dans `config/bundles.php`

**Étape 4 : Créer la configuration `config/packages/prism.yaml`**

```yaml
prism:
    enabled: '%kernel.debug%'
    yaml_path: '%kernel.project_dir%/prism/yaml'
    scripts_path: '%kernel.project_dir%/prism/scripts'
```

**Étape 5 : Créer les dossiers et copier les exemples**

```bash
# Créer les dossiers
mkdir -p prism/yaml prism/scripts

# Copier les fichiers d'exemple depuis la recette
cp PrismBundle/recipes/prism-bundle/1.0/prism/yaml/*.yaml.dist prism/yaml/
cp PrismBundle/recipes/prism-bundle/1.0/prism/scripts/*.php.dist prism/scripts/

# Retirer l'extension .dist pour activer les exemples
for f in prism/yaml/*.dist; do mv "$f" "${f%.dist}"; done
for f in prism/scripts/*.dist; do mv "$f" "${f%.dist}"; done
```

**Étape 6 : Configurer l'auto-discovery des scénarios PHP**

Créer `config/services/prism_scenarios.yaml` :

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Auto-discovery des scénarios PHP dans App\Prism
    App\Prism\:
        resource: '../../../prism/scripts/'
        tags: ['prism.scenario']
```

> ⚠️ **Note** : Décommentez cette section uniquement quand vous aurez des scénarios PHP dans `prism/scripts/`

**Étape 7 : Vider le cache et redémarrer**

```bash
rm -rf var/cache/*
# Si vous utilisez Docker
docker compose restart php
```

**Étape 8 : Vérifier l'installation**

```bash
php bin/console app:prism:list
```

> ℹ️ **Note** : La table `prism_resource` est créée automatiquement au premier usage (comme `doctrine_migration_versions`). Pas besoin de migration manuelle.

### Installation via Packagist (production)

Une fois le bundle publié sur Packagist :

```bash
composer require prism/bundle
```

Symfony Flex configurera automatiquement le bundle.

## 🗑️ Désinstallation

**Étape 1 : Retirer le bundle de `config/bundles.php`**

Supprimez la ligne :
```php
Prism\PrismBundle::class => ['all' => true],
```

**Étape 2 : Supprimer la configuration**

```bash
rm config/packages/prism.yaml
```

**Étape 3 : Retirer de `composer.json`**

Supprimez de la section `require` :
```json
"prism/bundle": "@dev"
```

Et de la section `repositories` :
```json
{
    "type": "path",
    "url": "./PrismBundle"
}
```

**Étape 4 : Supprimer la recette locale** (si installation locale)

```bash
rm -rf config/recipes/prism-bundle
```

**Étape 5 : Désinstaller via Composer**

```bash
composer update --no-scripts
rm -rf var/cache/*
```

**Étape 6 (optionnel) : Supprimer la table de tracking**

Si vous ne souhaitez plus conserver les traces :

```sql
DROP TABLE IF EXISTS prism_resource;
```

## 🚀 Utilisation Rapide

Voir [Guide de démarrage rapide](PRISM_QUICKSTART.md) pour un guide complet.

```bash
# Lister les scénarios
php bin/console app:prism:list

# Charger un scénario
php bin/console app:prism:load test_users --scope=dev_alice

# Purger un scénario
php bin/console app:prism:purge test_users --scope=dev_alice

# Purger tous les scénarios d'un scope
php bin/console app:prism:purge --scope=dev_alice --all
```

## 📝 Créer Votre Premier Scénario

### YAML (simple)

Créez `prism/my_prism.yaml` :

```yaml
load:
  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@example.com"
      password: "{{ hash('password123') }}"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
```

Chargez-le :

```bash
php bin/console app:prism:load my_prism --scope=test
```

### PHP (logique complexe)

Créez `prism/scripts/MyPrismPrism.php` :

```php
<?php

declare(strict_types=1);

namespace App\Prism;

use Prism\Application\Prism\AbstractPrism;
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
        
        // Insérer et tracker automatiquement
        $userId = $this->insertAndTrack('users', [
            'username' => sprintf('user_%s', $scopeStr),
            'email' => sprintf('user_%s@example.com', $scopeStr),
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable'
        ]);
        
        $this->logger->info('Utilisateur créé: {id}', ['id' => $userId]);
    }
}
```

## 🔥 Fonctionnalités Principales

Pour plus de détails, consultez la [documentation complète](PRISM.md).

### Isolation Multi-Scope

```bash
# Alice, Bob et QA travaillent en parallèle sans collision
php bin/console app:prism:load chat --scope=dev_alice
php bin/console app:prism:load chat --scope=dev_bob
php bin/console app:prism:load chat --scope=qa_sprint_42
```

### Support Multi-Database

Voir [Guide Multi-Database](MULTI_DATABASE.md).

```yaml
load:
  - table: users
    data:
      username: "admin_{{ scope }}"
  
  - table: audit_logs
    db: hexagonal_secondary  # Base secondaire
    data:
      action: "user_created"
```

### Lookups Dynamiques (FK)

```yaml
- table: posts
  data:
    author_id:
      table: users
      where:
        username: "admin_{{ scope }}"
      return: id
```

### 44 Types de Données Faker

```yaml
data:
  email: "{{ fake(email) }}"
  phone: "{{ fake(phone_fr) }}"
  iban: "{{ fake(iban_fr) }}"
  siret: "{{ fake(siret) }}"
```

Voir la [liste complète dans PRISM_YAML.md](PRISM_YAML.md#-44-types-de-données-faker).

## 📚 Documentation

### 🇫🇷 Français

- **[Guide de démarrage rapide](PRISM_QUICKSTART.md)** - Commencer en 2 minutes
- **[Documentation complète](PRISM.md)** - Référence complète
- **[Générateur Faker](PRISM_FAKER.md)** - 44 types de données (YAML + PHP)
- **[Scénarios YAML](PRISM_YAML.md)** - Variables, lookups, pipes
- **[Scénarios PHP](PRISM_PHP.md)** - API AbstractPrism, méthodes
- **[Scénarios Hybrides](PRISM_HYBRID.md)** - YAML + PHP
- **[Multi-Database](MULTI_DATABASE.md)** - Plusieurs bases de données
- **[Vue d'ensemble](PRISM_OVERVIEW.md)** - Architecture et workflow

### 🇬🇧 English

- **[Quick Start Guide](../en/PRISM_QUICKSTART.md)** - Get started in 2 minutes
- **[Complete Documentation](../en/PRISM.md)** - Full reference
- **[Faker Generator](../en/PRISM_FAKER.md)** - 44 data types (YAML + PHP)
- **[YAML Scenarios](../en/PRISM_YAML.md)** - Variables, lookups, pipes
- **[PHP Scenarios](../en/PRISM_PHP.md)** - AbstractPrism API, methods
- **[Hybrid Scenarios](../en/PRISM_HYBRID.md)** - YAML + PHP
- **[Multi-Database](../en/MULTI_DATABASE.md)** - Multiple databases
- **[Overview](../en/PRISM_OVERVIEW.md)** - Architecture and workflow

## 📊 Comparaison

| Fonctionnalité | Doctrine Fixtures | Alice (Nelmio) | Foundry | Laravel Seeders | **Prism** |
|----------------|-------------------|----------------|---------|-----------------|---------------|
| Isolation multi-scope | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Traçabilité complète | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Purge intelligent | ❌ | ❌ | ⚠️ Basique | ❌ | ✅ **UNIQUE** |
| Pivot custom | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Support multi-database | ❌ | ❌ | ⚠️ Complexe | ❌ | ✅ **UNIQUE** |
| Scénarios hybrides | ❌ | ⚠️ YAML+Faker | ✅ PHP Factories | ❌ | ✅ YAML+PHP **UNIQUE** |
| Imports modulaires | ❌ | ❌ | ⚠️ Stories | ❌ | ✅ **UNIQUE** |
| Variables globales YAML | ❌ | ⚠️ Paramètres | ❌ | ❌ | ✅ vars + $varname |
| Variables temporaires | ❌ | ❌ | ✅ Factory states | ❌ | ✅ **UNIQUE** |
| Placeholders (10+ types) | ❌ | ⚠️ 3-4 types | ❌ | ❌ | ✅ scope, hash, env, now, date, uuid, math, **fake**... |
| Lookups FK auto | ⚠️ Manuel | ✅ Références | ✅ Relations | ⚠️ Manuel | ✅ Lookups dynamiques |
| Pipes/Transformateurs | ❌ | ⚠️ Faker formatters | ⚠️ afterInstantiate | ❌ | ✅ 6 pipes chaînables |
| Math engine | ❌ | ❌ | ❌ | ❌ | ✅ **UNIQUE** |
| Données aléatoires | ❌ | ✅ Faker | ✅ Faker | ✅ Faker | ✅ **46 types, 0 dépendance** |
| Architecture hexagonale | ❌ | ❌ | ❌ | ❌ | ✅ Domain/App/Infra |
| Tests unitaires | ⚠️ Basique | ⚠️ Limité | ⚠️ Limité | ⚠️ Basique | ✅ 203 tests (PHPStan 9) | 

## ✅ Tests et Qualité

Le bundle est livré avec une **suite de tests complète** :

- **399 tests unitaires** avec **928 assertions**
- **100% de couverture** (Classes, Méthodes, Lignes)
- **0 mocks** : utilisation de FakeRepositories pour tests purs
- **27 fichiers de test** organisés par couche (Domain/Application/Infrastructure)
- **65 fichiers analysés** (38 src + 27 tests)

### 🚀 Contrôle Qualité Complet (Recommandé)

**Commande unique pour tout vérifier** (code source + tests) :

```bash
# Depuis le répertoire du bundle
vendor/bin/phpcs src tests --standard=phpcs.xml.dist && \
vendor/bin/phpstan analyse src -c phpstan.neon --level=9 --memory-limit=256M && \
vendor/bin/phpstan analyse tests -c phpstan.neon --level=9 --memory-limit=256M && \
vendor/bin/phpunit -c phpunit.xml.dist --no-coverage

# Depuis le projet racine avec Docker (⭐ RECOMMANDÉ)
docker compose exec php vendor/bin/phpcs PrismBundle/src PrismBundle/tests --standard=PrismBundle/phpcs.xml.dist && \
docker compose exec php vendor/bin/phpstan analyse PrismBundle/src -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M && \
docker compose exec php vendor/bin/phpstan analyse PrismBundle/tests -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M && \
docker compose exec php vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --no-coverage
```

**Ce contrôle vérifie :**
- ✅ **PHPCS** : Standards PSR-12 sur 65 fichiers (src + tests)
- ✅ **PHPStan src** : Analyse statique niveau 9 sur le code source (38 fichiers)
- ✅ **PHPStan tests** : Analyse statique niveau 9 sur les tests (27 fichiers)
- ✅ **PHPUnit** : Exécution de 203 tests avec 374 assertions

**Résultat attendu :**
```
✅ PHPCS: 0 violation sur 65 fichiers
✅ PHPStan src: 0 erreur sur 38 fichiers
✅ PHPStan tests: 0 erreur sur 27 fichiers
✅ PHPUnit: 399/399 tests passants, 928 assertions, 100% couverture
```

---

### 🧪 Tests Unitaires

Depuis le répertoire du bundle :

```bash
# Installation des dépendances de développement
cd PrismBundle
composer install

# Lancer tous les tests
vendor/bin/phpunit -c phpunit.xml.dist

# Lancer les tests sans coverage (plus rapide)
vendor/bin/phpunit -c phpunit.xml.dist --no-coverage

# Lancer avec couverture de code (100%)
php -d pcov.directory=. vendor/bin/phpunit -c phpunit.xml.dist --coverage-text

# Lancer un fichier de test spécifique
vendor/bin/phpunit -c phpunit.xml.dist tests/Application/YamlPrismTest.php

# Lancer un test spécifique
vendor/bin/phpunit -c phpunit.xml.dist --filter testLoadShouldHandleHashWithScope

# Lancer les tests des pipes uniquement
vendor/bin/phpunit -c phpunit.xml.dist --filter Pipe
```

Depuis le répertoire racine du projet (avec Docker) :

```bash
# Lancer tous les tests
docker compose exec php vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --no-coverage

# Lancer avec couverture de code (100%)
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-text

# Lancer un fichier de test spécifique
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist PrismBundle/tests/Application/YamlPrismTest.php

# Lancer les tests des pipes
docker compose exec php vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --filter Pipe --no-coverage
```

---

### 📋 Vérification du Style (PHPCS)

```bash
# Vérifier les violations PSR-12 sur src et tests
vendor/bin/phpcs src tests --standard=phpcs.xml.dist

# Depuis le projet racine
docker compose exec php vendor/bin/phpcs PrismBundle/src PrismBundle/tests --standard=PrismBundle/phpcs.xml.dist

# Corriger automatiquement les violations
vendor/bin/phpcbf src tests --standard=phpcs.xml.dist

# Depuis le projet racine
docker compose exec php vendor/bin/phpcbf PrismBundle/src PrismBundle/tests --standard=PrismBundle/phpcs.xml.dist

# Rapport détaillé avec résumé
vendor/bin/phpcs src tests --standard=phpcs.xml.dist --report=summary
```

---

### 🔍 Analyse Statique (PHPStan)

```bash
# Analyser le code source (niveau 9 maximum)
vendor/bin/phpstan analyse src -c phpstan.neon --level=9 --memory-limit=256M

# Analyser les tests (niveau 9 maximum)
vendor/bin/phpstan analyse tests -c phpstan.neon --level=9 --memory-limit=256M

# Depuis le projet racine - analyser src
docker compose exec php vendor/bin/phpstan analyse PrismBundle/src -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M

# Depuis le projet racine - analyser tests
docker compose exec php vendor/bin/phpstan analyse PrismBundle/tests -c PrismBundle/phpstan.neon --level=9 --memory-limit=256M
```

> **⚠️ Note importante** : L'analyse des tests nécessite `--memory-limit=256M` car PHPStan doit scanner toutes les classes fake. L'analyse du code source peut aussi en avoir besoin selon la complexité.

---

### 📊 Couverture de Code

**Depuis le répertoire du bundle** (sans Docker) :

```bash
# Avec PCOV (recommandé - plus rapide)
php -d pcov.enabled=1 vendor/bin/phpunit -c phpunit.xml.dist --coverage-html var/report
php -d pcov.enabled=1 vendor/bin/phpunit -c phpunit.xml.dist --coverage-text

# Avec Xdebug (plus lent)
XDEBUG_MODE=coverage vendor/bin/phpunit -c phpunit.xml.dist --coverage-html var/report
XDEBUG_MODE=coverage vendor/bin/phpunit -c phpunit.xml.dist --coverage-text

# Ouvrir le rapport HTML
open var/report/index.html  # macOS
xdg-open var/report/index.html  # Linux
```

**Depuis le projet racine avec Docker** (⭐ **RECOMMANDÉ**) :

```bash
# Générer le rapport HTML avec PCOV
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-html PrismBundle/var/report

# Générer un rapport texte dans le terminal
docker compose exec php php -d pcov.directory=/var/www/html/PrismBundle vendor/bin/phpunit -c PrismBundle/phpunit.xml.dist --coverage-text

# Ouvrir le rapport HTML
xdg-open PrismBundle/var/report/index.html  # Linux
open PrismBundle/var/report/index.html  # macOS
```

> **💡 Astuce PCOV** : L'option `-d pcov.directory=/var/www/html/PrismBundle` est **essentielle** dans Docker pour que PCOV scanne le bon répertoire. Sans cela, la couverture sera à 0%.

---

### 🎯 Standards de Qualité

- ✅ **399 tests unitaires** (952 assertions, 0 mocks)
- ✅ **Couverture** 100% Classes (17/17), 100% Méthodes (133/133), 100% Lignes (923/923)
- ✅ **PHPStan niveau 9** 0 erreur sur 65 fichiers (38 src + 27 tests)
- ✅ **PSR-12 PHPCS** 0 violation sur 65 fichiers (src + tests)
- ✅ **PHP 8.3** typage strict
- ✅ **Architecture** Hexagonale pure (Domain/Application/Infrastructure)

Voir [Tests complets](PRISM.md#-tests-et-qualité) pour les commandes.

## 📄 Licence

MIT - Voir fichier `LICENSE`.

## 👤 Auteur

**David Lhoumaud**
- Email: dlhoumaud@gmail.com

Voir [prism-office](https://github.com/dlhoumaud/prism-office) pour l'interface graphique.