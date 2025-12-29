<?php

declare(strict_types=1);

namespace Prism\Application\Contract;

/**
 * Port : Repository pour les opérations de données des scénarios
 *
 * Interface permettant aux scénarios d'insérer/supprimer des données
 * sans dépendre directement de Doctrine.
 */
interface PrismDataRepositoryInterface
{
    /**
     * Insère une ligne dans une table et retourne l'ID
     *
     * @param string $tableName
     * @param array<string, mixed> $data
     * @param array<string, string> $types Types DBAL (datetime_immutable, etc.)
     * @return int|string|null ID de la ligne insérée
     * (int si auto-increment, string si fourni dans data, null si pas d'ID)
     */
    public function insert(string $tableName, array $data, array $types = []): int|string|null;

    /**
     * Supprime des lignes d'une table selon des conditions
     *
     * @param string $tableName
     * @param array<string, mixed> $where Conditions WHERE (column => value)
     * @return int Nombre de lignes supprimées
     */
    public function delete(string $tableName, array $where): int;

    /**
     * Exécute une requête SQL brute
     *
     * @param string $sql
     * @param array<string, mixed> $params
     * @return int Nombre de lignes affectées
     */
    public function executeStatement(string $sql, array $params = []): int;

    /**
     * Exécute une requête SELECT et retourne les résultats
     *
     * @param string $sql
     * @param array<string, mixed> $params
     * @return array<array<string, mixed>> Résultats de la requête
     */
    public function executeQuery(string $sql, array $params = []): array;
}
