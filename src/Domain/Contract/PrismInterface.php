<?php

declare(strict_types=1);

namespace Prism\Domain\Contract;

use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;

/**
 * Port : Définit le contrat d'un scénario fonctionnel
 *
 * Un scénario est responsable de créer un contexte métier complet et déterministe
 * pour les tests fonctionnels.
 */
interface PrismInterface
{
    /**
     * Retourne le nom unique du scénario
     */
    public function getName(): PrismName;

    /**
     * Charge le scénario en créant les données nécessaires
     *
     * @param Scope $scope Scope d'isolation des données
     * @throws \Prism\Domain\Exception\PrismLoadException
     */
    public function load(Scope $scope): void;

    /**
     * Purge toutes les données créées par le scénario pour un scope donné
     *
     * @param Scope $scope Scope des données à purger
     * @throws \Prism\Domain\Exception\ScopePurgeFailedException
     */
    public function purge(Scope $scope): void;
}
