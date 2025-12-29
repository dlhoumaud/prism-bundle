<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Configurator;

use Prism\Domain\Contract\DatabaseConnectionInterface;
use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;

/**
 * Configurateur : Configure dynamiquement les scénarios
 *
 * Permet d'injecter le contexte avec le bon scope au moment de l'exécution.
 */
final class PrismConfigurator
{
    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly PrismResourceTrackerInterface $tracker
    ) {
    }

    /**
     * Configure un scénario avec son contexte
     *
     * Note: Cette méthode est appelée par le container Symfony
     * lors de l'instanciation du scénario.
     */
    public function configure(PrismInterface $prism, string $scope = 'default'): void
    {
        // Le contexte est déjà injecté via le constructeur
        // Cette méthode peut être étendue si nécessaire
    }
}
