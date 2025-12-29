<?php

declare(strict_types=1);

namespace Prism\Domain\Contract;

use Prism\Domain\Entity\PrismResource;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\TableName;

/**
 * Port : Interface pour tracer les ressources créées par les scénarios
 *
 * Permet de garder une trace de toutes les données créées par un scénario
 * afin de pouvoir les supprimer de manière fiable lors du purge.
 */
interface PrismResourceTrackerInterface
{
    /**
     * Enregistre une ressource créée par un scénario
     *
     * @param PrismName $prismName Nom du scénario
     * @param Scope $scope Scope d'isolation
     * @param TableName $tableName Nom de la table
     * @param int $rowId ID de la ligne créée
     * @param string $idColumnName Nom de la colonne ID
     * @param string|null $dbName Nom de la base de données (null = base par défaut)
     */
    public function track(
        PrismName $prismName,
        Scope $scope,
        TableName $tableName,
        string|int $rowId,
        string $idColumnName = 'id',
        ?string $dbName = null
    ): void;

    /**
     * Récupère toutes les ressources d'un scénario pour un scope donné
     *
     * @param PrismName $prismName
     * @param Scope $scope
     * @return PrismResource[]
     */
    public function findByPrismAndScope(PrismName $prismName, Scope $scope): array;

    /**
     * Supprime tous les enregistrements de tracking pour un scénario et un scope
     *
     * @param PrismName $prismName
     * @param Scope $scope
     */
    public function deleteByPrismAndScope(PrismName $prismName, Scope $scope): void;

    /**
     * Supprime tous les enregistrements de tracking pour un scope (tous scénarios)
     *
     * @param Scope $scope
     */
    public function deleteByScope(Scope $scope): void;
}
