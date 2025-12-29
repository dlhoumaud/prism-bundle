<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Prism\Domain\Contract\DatabaseConnectionInterface;

/**
 * Adaptateur : Wrapper autour de Doctrine DBAL Connection
 *
 * Implémente l'interface DatabaseConnectionInterface du domaine
 * en déléguant à la connexion Doctrine DBAL.
 */
final class DbalDatabaseConnection implements DatabaseConnectionInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }
}
