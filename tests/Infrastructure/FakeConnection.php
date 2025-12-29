<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\ServerVersionProvider;

/**
 * Fake Repository : Connexion Doctrine DBAL minimale pour les tests
 *
 * Crée une instance minimale de Connection pour les tests
 */
final class FakeConnection
{
    private static ?Connection $instance = null;

    /**
     * Retourne une instance minimale de Connection pour les tests
     */
    public static function create(): Connection
    {
        if (self::$instance === null) {
            // Créer un Driver minimal
            $driver = new class implements Driver {
                public function connect(array $params): Driver\Connection
                {
                    return new class implements Driver\Connection {
                        public function prepare(string $sql): Driver\Statement
                        {
                            throw new \RuntimeException('Not implemented in FakeConnection');
                        }

                        public function query(string $sql): Driver\Result
                        {
                            return new FakeResult();
                        }

                        public function exec(string $sql): int
                        {
                            return 0;
                        }

                        public function lastInsertId(): string|int
                        {
                            return 1;
                        }

                        public function beginTransaction(): void
                        {
                        }

                        public function commit(): void
                        {
                        }

                        public function rollBack(): void
                        {
                        }

                        public function quote(string $value): string
                        {
                            return "'" . addslashes($value) . "'";
                        }

                        public function getServerVersion(): string
                        {
                            return '3.0.0';
                        }

                        public function getNativeConnection(): object
                        {
                            return (object) ['fake' => true];
                        }
                    };
                }

                public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
                {
                    return new SQLitePlatform();
                }

                /** @return AbstractSchemaManager<SQLitePlatform> */
                public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
                {
                    throw new \RuntimeException('Not implemented in FakeConnection');
                }

                public function getExceptionConverter(): ExceptionConverter
                {
                    throw new \RuntimeException('Not implemented in FakeConnection');
                }
            };

            self::$instance = new Connection(
                ['driver' => 'pdo_sqlite', 'memory' => true],
                $driver
            );
        }

        return self::$instance;
    }
}
