<?php

declare(strict_types=1);

namespace Prism\Application\DTO;

use Prism\Domain\Contract\DatabaseConnectionInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\ValueObject\Scope;

/**
 * DTO : Contexte d'exécution d'un scénario
 *
 * Regroupe toutes les dépendances nécessaires à l'exécution d'un scénario.
 */
final class PrismContext
{
    public function __construct(
        public readonly Scope $scope,
        public readonly DatabaseConnectionInterface $connection,
        public readonly PrismResourceTrackerInterface $tracker
    ) {
    }
}
