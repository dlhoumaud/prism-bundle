<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\Entity\PrismResource;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\TableName;

/**
 * Adaptateur : Implémentation Doctrine DBAL du tracker de ressources
 *
 * Gère la table pivot prism_resource pour tracer toutes les données
 * créées par les scénarios.
 *
 * La table est créée automatiquement au premier usage (comme doctrine_migration_versions).
 */
final class DoctrinePrismResourceTracker implements PrismResourceTrackerInterface
{
    private const TABLE_NAME = 'prism_resource';
    private bool $tableChecked = false;

    public function __construct(
        private Connection $connection
    ) {
    }

    public function track(
        PrismName $prismName,
        Scope $scope,
        TableName $tableName,
        string|int $rowId,
        string $idColumnName = 'id',
        ?string $dbName = null
    ): void {
        $this->ensureTableExists();

        $this->connection->insert(self::TABLE_NAME, [
            'prism_name' => $prismName->toString(),
            'scope' => $scope->toString(),
            'table_name' => $tableName->toString(),
            'row_id' => (string) $rowId,
            'id_column_name' => $idColumnName,
            'db_name' => $dbName,
            'created_at' => new \DateTimeImmutable()
        ], [
            'created_at' => 'datetime_immutable'
        ]);
    }

    public function findByPrismAndScope(PrismName $prismName, Scope $scope): array
    {
        $this->ensureTableExists();

        $qb = $this->connection->createQueryBuilder();

        /** @var list<array{prism_name: string, scope: string, table_name: string, row_id: string|int, id_column_name: string, db_name: string|null, created_at: string}> $results */
        $results = $qb
            ->select('prism_name', 'scope', 'table_name', 'row_id', 'id_column_name', 'db_name', 'created_at')
            ->from(self::TABLE_NAME)
            ->where('prism_name = :prism')
            ->andWhere('scope = :scope')
            ->orderBy('id', 'ASC')
            ->setParameter('prism', $prismName->toString())
            ->setParameter('scope', $scope->toString())
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            fn(array $row) => PrismResource::fromArray($row),
            $results
        );
    }

    public function deleteByPrismAndScope(PrismName $prismName, Scope $scope): void
    {
        $this->ensureTableExists();

        $this->connection->delete(self::TABLE_NAME, [
            'prism_name' => $prismName->toString(),
            'scope' => $scope->toString()
        ]);
    }

    public function deleteByScope(Scope $scope): void
    {
        $this->ensureTableExists();

        $this->connection->delete(self::TABLE_NAME, [
            'scope' => $scope->toString()
        ]);
    }

    /**
     * Vérifie et crée la table prism_resource si elle n'existe pas
     *
     * Comme doctrine_migration_versions, cette table d'infrastructure
     * est créée automatiquement au premier usage.
     */
    private function ensureTableExists(): void
    {
        if ($this->tableChecked) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE_NAME])) {
            $this->createTable();
        }

        $this->tableChecked = true;
    }

    /**
     * Crée la table prism_resource selon le SGBD utilisé
     */
    private function createTable(): void
    {
        $platform = $this->connection->getDatabasePlatform();

        // MariaDB utilise une plateforme spécifique mais est compatible MySQL
        if ($platform instanceof AbstractMySQLPlatform) {
            $this->createMySQLTable();
            return;
        }

        if ($platform instanceof PostgreSQLPlatform) {
            $this->createPostgreSQLTable();
            return;
        }

        if ($platform instanceof SQLitePlatform) {
            $this->createSQLiteTable();
            return;
        }

        throw new \RuntimeException(
            sprintf('Unsupported database platform: %s', $platform::class)
        );
    }

    private function createMySQLTable(): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS prism_resource (
                id INT AUTO_INCREMENT PRIMARY KEY,
                prism_name VARCHAR(255) NOT NULL,
                scope VARCHAR(255) NOT NULL,
                table_name VARCHAR(255) NOT NULL,
                row_id VARCHAR(255) NOT NULL,
                id_column_name VARCHAR(255) DEFAULT 'id' NOT NULL,
                db_name VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_prism_scope (prism_name, scope),
                INDEX idx_table_row (table_name, row_id),
                UNIQUE KEY unique_tracking (prism_name, scope, table_name, row_id, id_column_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->connection->executeStatement($sql);
    }

    private function createPostgreSQLTable(): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS prism_resource (
                id SERIAL PRIMARY KEY,
                prism_name VARCHAR(255) NOT NULL,
                scope VARCHAR(255) NOT NULL,
                table_name VARCHAR(255) NOT NULL,
                row_id VARCHAR(255) NOT NULL,
                id_column_name VARCHAR(255) DEFAULT 'id' NOT NULL,
                db_name VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL,
                CONSTRAINT unique_tracking UNIQUE (prism_name, scope, table_name, row_id, id_column_name)
            );
            
            CREATE INDEX IF NOT EXISTS idx_prism_scope ON prism_resource (prism_name, scope);
            CREATE INDEX IF NOT EXISTS idx_table_row ON prism_resource (table_name, row_id);
        SQL;

        $this->connection->executeStatement($sql);
    }

    private function createSQLiteTable(): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS prism_resource (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                prism_name VARCHAR(255) NOT NULL,
                scope VARCHAR(255) NOT NULL,
                table_name VARCHAR(255) NOT NULL,
                row_id VARCHAR(255) NOT NULL,
                id_column_name VARCHAR(255) DEFAULT 'id' NOT NULL,
                db_name VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE(prism_name, scope, table_name, row_id, id_column_name)
            );
            
            CREATE INDEX IF NOT EXISTS idx_prism_scope ON prism_resource (prism_name, scope);
            CREATE INDEX IF NOT EXISTS idx_table_row ON prism_resource (table_name, row_id);
        SQL;

        $this->connection->executeStatement($sql);
    }
}
