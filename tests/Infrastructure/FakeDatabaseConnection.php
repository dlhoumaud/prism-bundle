<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Doctrine\DBAL\Connection;
use Prism\Domain\Contract\DatabaseConnectionInterface;

/**
 * Fake Repository : Connexion base de données pour les tests
 */
final class FakeDatabaseConnection implements DatabaseConnectionInterface
{
    private bool $inTransaction = false;
    private int $transactionCount = 0;
    private int $commitCount = 0;
    private int $rollbackCount = 0;
    private bool $shouldFailOnCommit = false;
    private ?Connection $connection = null;

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection;
    }

    public function getConnection(): Connection
    {
        if ($this->connection === null) {
            throw new \RuntimeException('getConnection() called but no Connection provided to FakeDatabaseConnection');
        }

        return $this->connection;
    }

    public function beginTransaction(): void
    {
        $this->inTransaction = true;
        $this->transactionCount++;
    }

    public function commit(): void
    {
        if ($this->shouldFailOnCommit) {
            throw new \RuntimeException('Simulated commit failure');
        }

        $this->inTransaction = false;
        $this->commitCount++;
    }

    public function rollback(): void
    {
        $this->inTransaction = false;
        $this->rollbackCount++;
    }

    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function getTransactionCount(): int
    {
        return $this->transactionCount;
    }

    public function getCommitCount(): int
    {
        return $this->commitCount;
    }

    public function getRollbackCount(): int
    {
        return $this->rollbackCount;
    }

    public function simulateCommitFailure(): void
    {
        $this->shouldFailOnCommit = true;
    }

    public function reset(): void
    {
        $this->inTransaction = false;
        $this->transactionCount = 0;
        $this->commitCount = 0;
        $this->rollbackCount = 0;
        $this->shouldFailOnCommit = false;
    }
}
