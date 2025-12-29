# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- ✅ **Multi-Database Support** - Work with multiple databases in scenarios
  - `db` parameter in YAML load instructions
  - `db` parameter in YAML purge instructions
  - `db` parameter in YAML lookup fields
  - `dbName` parameter in `insertAndTrack()` method
  - `dbName` parameter in `trackResource()` method
  - `db_name` column in `prism_resource` table for tracking
  - Automatic database prefix in DELETE statements during purge
  - Full support in YamlPrism for multi-database scenarios
  - Documentation updated in PRISM_YAML.md, PRISM_PHP.md and MULTI_DATABASE.md
- ✅ **DatabaseNameResolver** - Doctrine connection name resolution with `%connection%` syntax
  - Detect `%connection_name%` pattern via regex `/^%(.+)%$/`
  - Extract database name from Doctrine connection parameters
  - Support for both direct database name and Doctrine connection name
  - `DatabaseNameResolverInterface` domain contract
  - `DatabaseNameResolver` infrastructure implementation
  - Integration in `AbstractPrism`, `YamlPrism` and all Prism classes
  - Example: `db: %secondary%` resolves to `hexagonal_secondary`
- ✅ **DoctrinePrismDataRepository Multi-Connection Refactor**
  - Use `Doctrine\Persistence\ConnectionRegistry` to access all connections
  - `getConnectionForDatabase()` method to map database name → Doctrine connection
  - `parseTableName()` to extract database.table format
  - `extractDbNameFromUrl()` helper to parse database name from URL
  - Support for cross-database lookups
  - Transaction management per connection

### Fixed
- 🐛 **ConnectionRegistry iteration bug** - Fixed `getConnectionNames()` usage
  - Was: `foreach ($connectionNames as $name)` using array values (service IDs)
  - Now: `foreach ($connectionNames as $name => $serviceId)` using array keys (connection names)
  - Prevents "Connection not found" errors when using multiple databases

## [1.0.0] - 2025-12-20

### Added
- 🎉 Initial release du PrismBundle
- ✅ Isolation multi-scope pour développement en équipe sans collision
- ✅ Table pivot pour traçabilité complète des ressources créées
- ✅ Purge intelligent en ordre inverse respectant les FK
- ✅ Pivot custom permettant le tracking par n'importe quelle colonne
- ✅ Support des scénarios YAML (déclaratif)
- ✅ Support des scénarios PHP (impératif)
- ✅ Support des scénarios hybrides (YAML + PHP)
- ✅ Système d'imports modulaires pour composition de scénarios
- ✅ Lookups dynamiques pour résolution automatique des FK
- ✅ Math engine pour calculs dans les scénarios YAML
- ✅ Dates relatives ({{ date('+7 days') }})
- ✅ Variables d'environnement ({{ env('VAR') }})
- ✅ Variables globales et temporaires en YAML
- ✅ Architecture hexagonale complète (Domain/Application/Infrastructure)
- ✅ 3 commandes CLI : list, load, purge
- ✅ Documentation complète (7 guides)
- ✅ Tests unitaires (78 tests, 176 assertions, 100% coverage Application layer)
  * 14 fichiers de test dans tests/Application, tests/Domain, tests/Infrastructure
  * 7 FakeRepositories pour tests sans mocks
  * Configuration PHPUnit avec bootstrap
- ✅ Conformité PSR-12
- ✅ PHPStan level max

### Features principales
- **Scénarios YAML** : 10+ types de placeholders, lookups FK, math engine, dates relatives
- **Scénarios PHP** : API `insertAndTrack()`, accès repository, tracking manuel, purge auto
- **Scénarios Hybrides** : Combine YAML (structure) + PHP (logique métier)
- **Isolation** : Scopes illimités simultanés sans collision
- **Traçabilité** : Table `prism_resource` trackant chaque donnée
- **Purge** : Ordre inverse automatique, respect des contraintes FK
- **Pivot Custom** : Track par user_id, customer_id, etc. au lieu de id
- **Imports** : Composition modulaire de scénarios (base_users, base_acl, etc.)
- **Transactions** : BEGIN/COMMIT automatique avec rollback en cas d'erreur

### Documentation
- README.md : Guide d'installation et démarrage rapide
- SCENARIO_QUICKSTART.md : Quick start (2 minutes)
- PRISM.md : Documentation exhaustive
- SCENARIO_YAML.md : Guide complet des scénarios YAML
- SCENARIO_PHP.md : Guide complet des scénarios PHP
- SCENARIO_HYBRID.md : Guide des scénarios hybrides
- SCENARIO_OVERVIEW.md : Vue d'ensemble du système

[1.0.0]: https://github.com/dlhoumaud/prism-bundle/releases/tag/v1.0.0
