<?php

declare(strict_types=1);

namespace Prism\Application\Prism;

use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\Contract\FakeDataGeneratorInterface;
use Prism\Domain\Contract\DatabaseNameResolverInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\TableName;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Classe abstraite facilitant l'implémentation de scénarios
 *
 * Fournit des méthodes utilitaires pour :
 * - Accéder au repository de données
 * - Tracer les ressources créées
 * - Purger automatiquement via le tracker
 * - Générer des données aléatoires
 */
abstract class AbstractPrism implements PrismInterface
{
    protected readonly LoggerInterface $logger;
    protected ?Scope $currentScope = null;

    public function __construct(
        protected readonly PrismDataRepositoryInterface $repository,
        protected readonly PrismResourceTrackerInterface $tracker,
        protected readonly FakeDataGeneratorInterface $fakeGenerator,
        protected readonly DatabaseNameResolverInterface $dbNameResolver,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Récupère le repository de données
     */
    protected function getRepository(): PrismDataRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Récupère le scope actuel
     */
    protected function getScope(): Scope
    {
        if ($this->currentScope === null) {
            throw new \RuntimeException('Le scope n\'a pas été défini. Appelez load() ou purge() d\'abord.');
        }
        return $this->currentScope;
    }

    /**
     * Trace une ressource créée
     *
     * @param string $tableName Nom de la table
     * @param string|int $rowId ID de la ligne créée (supporte INT et VARCHAR)
     * @param string $idColumnName Nom de la colonne ID dans la table (par défaut 'id')
     * @param string|null $dbName Nom de la base ou %connection% (null = base par défaut)
     */
    protected function trackResource(
        string $tableName,
        string|int $rowId,
        string $idColumnName = 'id',
        ?string $dbName = null
    ): void {
        // Résoudre %connection% vers nom de base réel
        $resolvedDbName = $this->dbNameResolver->resolve($dbName);

        $this->tracker->track(
            $this->getName(),
            $this->getScope(),
            TableName::fromString($tableName),
            $rowId,
            $idColumnName,
            $resolvedDbName
        );

        $logContext = [
            'table' => $tableName,
            'id' => $rowId,
            'column' => $idColumnName
        ];

        if ($resolvedDbName !== null) {
            $logContext['db'] = $resolvedDbName;
        }

        $this->logger->debug('Ressource tracée: {table}#{id} (colonne: {column})', $logContext);
    }

    /**
     * Insère une ligne et la trace automatiquement
     *
     * @param string $tableName
     * @param array<string, mixed> $data
     * @param array<string, string> $types
     * @param string $idColumnName Nom de la colonne ID pour le tracking (défaut: 'id')
     * @param string|null $dbName Nom de la base ou %connection% (null = base par défaut)
     * @return int|string|null ID de la ligne insérée
     */
    protected function insertAndTrack(
        string $tableName,
        array $data,
        array $types = [],
        string $idColumnName = 'id',
        ?string $dbName = null
    ): int|string|null {
        // Résoudre %connection% vers nom de base réel
        $resolvedDbName = $this->dbNameResolver->resolve($dbName);

        // Construire le nom de table complet si dbName est fourni
        $fullTableName = $resolvedDbName !== null ? "$resolvedDbName.$tableName" : $tableName;

        $lastId = $this->repository->insert($fullTableName, $data, $types);

        // Ne tracker que si on a récupéré un ID
        if ($lastId !== null) {
            $this->trackResource($tableName, $lastId, $idColumnName, $dbName);
        }

        return $lastId;
    }

    /**
     * Génère une donnée aléatoire
     *
     * @param string $type Type de donnée (user, email, tel, date, etc.)
     * @param mixed ...$params Paramètres optionnels selon le type
     * @return string|int|float|bool Donnée générée
     */
    protected function fake(string $type, mixed ...$params): string|int|float|bool
    {
        $scope = $this->currentScope?->toString();
        return $this->fakeGenerator->generate($type, $scope, ...$params);
    }

    /**
     * Implémentation par défaut du purge via le tracker
     *
     * Supprime les données en ordre inverse de création pour respecter
     * les contraintes de clés étrangères.
     */
    public function purge(Scope $scope): void
    {
        $this->currentScope = $scope;

        $resources = $this->tracker->findByPrismAndScope(
            $this->getName(),
            $scope
        );

        $this->logger->info('Purge de {count} ressources pour le scénario {prism} (scope: {scope})', [
            'count' => count($resources),
            'prism' => $this->getName()->toString(),
            'scope' => $scope->toString()
        ]);

        // Suppression en ordre inverse de création
        foreach (array_reverse($resources) as $resource) {
            try {
                // Construire le nom de table complet si db_name est présent
                $fullTableName = $resource->dbName !== null
                    ? "{$resource->dbName}.{$resource->tableName->toString()}"
                    : $resource->tableName->toString();

                $this->repository->executeStatement(
                    sprintf('DELETE FROM %s WHERE %s = :id', $fullTableName, $resource->idColumnName),
                    ['id' => $resource->rowId]
                );

                $this->logger->debug('Ressource supprimée: {table}.{column}={id}', [
                    'table' => $fullTableName,
                    'column' => $resource->idColumnName,
                    'id' => $resource->rowId
                ]);
            } catch (\Throwable $e) {
                // Log mais continue (la ligne a peut-être déjà été supprimée par cascade)
                $fullTableName = $resource->dbName !== null
                    ? "{$resource->dbName}.{$resource->tableName->toString()}"
                    : $resource->tableName->toString();

                $this->logger->warning('Impossible de supprimer {table}.{column}={id}: {error}', [
                    'table' => $fullTableName,
                    'column' => $resource->idColumnName,
                    'id' => $resource->rowId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Nettoyage des traces
        $this->tracker->deleteByPrismAndScope($this->getName(), $scope);
    }
}
