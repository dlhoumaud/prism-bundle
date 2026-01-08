<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Domain\Contract\DatabaseNameResolverInterface;

/**
 * Implémentation Doctrine du repository de données pour les scénarios
 */
final class DoctrinePrismDataRepository implements PrismDataRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ConnectionRegistry $registry,
        private readonly DatabaseNameResolverInterface $dbNameResolver
    ) {
    }

    public function insert(string $tableName, array $data, array $types = []): int|string|null
    {
        // Extraire database.table si présent
        [$dbName, $table] = $this->parseTableName($tableName);

        // Utiliser la connexion appropriée
        $conn = $dbName !== null ? $this->getConnectionForDatabase($dbName) : $this->connection;

        $conn->insert($table, $data, $types);

        // Si l'ID est fourni dans les données (VARCHAR par exemple), on le retourne
        if (isset($data['id'])) {
            $id = $data['id'];
            return is_int($id) || is_string($id) ? $id : null;
        }

        // Essayer de récupérer l'auto-increment ID
        try {
            $lastId = $conn->lastInsertId();
            return $lastId !== false && $lastId !== '' ? (int) $lastId : null;
        } catch (\Throwable) {
            // Pas d'auto-increment (ex: users_acl sans PK auto)
            return null;
        }
    }

    /**
     * Parse table name en format database.table
     *
     * @return array{0: string|null, 1: string}
     */
    private function parseTableName(string $tableName): array
    {
        if (str_contains($tableName, '.')) {
            $parts = explode('.', $tableName, 2);
            return [$parts[0], $parts[1]];
        }

        return [null, $tableName];
    }

    /**
     * Récupère la connexion Doctrine pour une base de données
     */
    private function getConnectionForDatabase(string $dbName): Connection
    {
        // Essayer de trouver une connexion qui cible cette base
        $connectionNames = $this->registry->getConnectionNames();

        foreach ($connectionNames as $name => $serviceId) {
            /** @var Connection $conn */
            $conn = $this->registry->getConnection($name);
            $params = $conn->getParams();

            // Vérifier si cette connexion cible la bonne base
            if (isset($params['dbname']) && $params['dbname'] === $dbName) {
                return $conn;
            }
        }

        // Fallback : utiliser la connexion par défaut
        return $this->connection;
    }

    public function delete(string $tableName, array $where): int
    {
        if (empty($where)) {
            throw new \InvalidArgumentException('DELETE without WHERE clause is not allowed');
        }

        $whereClauses = [];
        $params = [];
        foreach ($where as $column => $value) {
            $whereClauses[] = sprintf('%s = :%s', $column, $column);
            $params[$column] = $value;
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $tableName,
            implode(' AND ', $whereClauses)
        );

        // Use executeStatement to ensure correct connection selection
        return $this->executeStatement($sql, $params);
    }

    public function executeStatement(string $sql, array $params = []): int
    {
        // Try to detect a database name in the SQL (e.g. FROM db.table or DELETE FROM db.table)
        $dbName = $this->extractDatabaseFromSql($sql);

        $conn = $dbName !== null ? $this->getConnectionForDatabase($dbName) : $this->connection;

        $rowCount = $conn->executeStatement($sql, $params);
        return is_int($rowCount) ? $rowCount : 0;
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        // Détecter si le SQL cible une base spécifique et utiliser la connexion correspondante
        $dbName = $this->extractDatabaseFromSql($sql);
        $conn = $dbName !== null ? $this->getConnectionForDatabase($dbName) : $this->connection;

        $result = $conn->executeQuery($sql, $params);
        return $result->fetchAllAssociative();
    }

    /**
     * Tente d'extraire un nom de base depuis une requête SQL simple.
     * Supporte les formes : FROM db.table ou DELETE FROM db.table
     */
    private function extractDatabaseFromSql(string $sql): ?string
    {
        // Rechercher "FROM <db>.<table>" ou "DELETE FROM <db>.<table>"
        if (preg_match('/\bFROM\s+(`?)([A-Za-z0-9_]+)\1\.(?:`?)([A-Za-z0-9_]+)(?:`)?/i', $sql, $matches)) {
            return $matches[2];
        }

        return null;
    }
}
