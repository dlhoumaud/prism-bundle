<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ConnectionRegistry;
use Prism\Domain\Contract\DatabaseNameResolverInterface;

/**
 * Résout les noms de connexion Doctrine vers les noms de bases de données
 *
 * Permet d'utiliser %connection_name% au lieu du nom de base directement
 * Supporte aussi les alias depuis shared.db (logiciel, extranet, common, etc.)
 */
final class DatabaseNameResolver implements DatabaseNameResolverInterface
{
    /**
     * @param array<string, string> $databaseAliases Map des alias vers noms de BDD (ex: ['logiciel' => 'safti_omega'])
     */
    public function __construct(
        private readonly ConnectionRegistry $registry,
        private readonly array $databaseAliases = []
    ) {
    }

    /**
     * Résout un nom de base de données
     *
     * Supporte plusieurs formats :
     * - %connection_name% : Connexion Doctrine (ex: %default%, %secondary%)
     * - %alias% : Alias depuis shared.db (ex: %incare%, %logiciel%, %extranet%)
     * - alias : Alias direct sans % (ex: incare, logiciel, extranet)
     * - nom_direct : Nom de BDD direct
     *
     * @param string|null $dbName Nom de base, alias ou %connection_name%
     * @return string|null Nom de base résolu ou null
     */
    public function resolve(?string $dbName): ?string
    {
        if ($dbName === null) {
            return null;
        }

        // Détecter le pattern %something%
        if (preg_match('/^%(.+)%$/', $dbName, $matches)) {
            $name = $matches[1];

            // D'abord vérifier si c'est un alias depuis shared.db
            if (isset($this->databaseAliases[$name])) {
                return $this->databaseAliases[$name];
            }

            // Sinon, essayer comme connexion Doctrine
            return $this->extractDatabaseName($name);
        }

        // Vérifier si c'est un alias direct (sans %) depuis shared.db
        if (isset($this->databaseAliases[$dbName])) {
            return $this->databaseAliases[$dbName];
        }

        // Retourner tel quel (nom de BDD direct)
        return $dbName;
    }

    /**
     * Extrait le nom de la base depuis une connexion Doctrine
     *
     * @param string $connectionName Nom de la connexion (ex: "secondary")
     * @return string Nom de la base de données
     * @throws \RuntimeException Si la connexion n'existe pas ou si le nom ne peut être extrait
     */
    private function extractDatabaseName(string $connectionName): string
    {
        try {
            /** @var \Doctrine\DBAL\Connection $connection */
            $connection = $this->registry->getConnection($connectionName);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'Cannot get Doctrine connection "%s": %s. Available connections: %s',
                $connectionName,
                $e->getMessage(),
                implode(', ', array_keys($this->registry->getConnectionNames()))
            ), 0, $e);
        }

        // Récupérer les paramètres de connexion
        $params = $connection->getParams();

        // Récupérer dbname depuis les params
        if (isset($params['dbname'])) {
            return (string) $params['dbname'];
        }

        throw new \RuntimeException(sprintf(
            'Cannot extract database name from connection "%s"',
            $connectionName
        ));
    }
}
