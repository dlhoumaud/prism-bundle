<?php

declare(strict_types=1);

namespace Prism\Application\UseCase;

use Prism\Domain\Contract\DatabaseConnectionInterface;
use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\Exception\PrismLoadException;
use Prism\Domain\ValueObject\Scope;
use Psr\Log\LoggerInterface;

/**
 * Use Case : Charger un scénario fonctionnel
 *
 * Ce cas d'usage est responsable de :
 * - Purger les données existantes du même scope
 * - Charger les nouvelles données du scénario
 * - Garantir l'atomicité via transaction
 */
final class LoadPrism
{
    public function __construct(
        private readonly DatabaseConnectionInterface $databaseConnection,
        private readonly PrismResourceTrackerInterface $tracker,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(PrismInterface $prism, Scope $scope): void
    {
        $prismName = $prism->getName();

        $this->logger->info('Chargement du scénario {prism} pour le scope {scope}', [
            'prism' => $prismName->toString(),
            'scope' => $scope->toString()
        ]);

        try {
            $this->databaseConnection->beginTransaction();

            // Purge préalable si nécessaire
            $this->logger->debug('Purge du scope {scope} avant chargement', [
                'scope' => $scope->toString()
            ]);
            $prism->purge($scope);

            // Chargement du scénario
            $this->logger->debug('Chargement des données du scénario {prism}', [
                'prism' => $prismName->toString()
            ]);
            $prism->load($scope);

            $this->databaseConnection->commit();

            $this->logger->info('Scénario {prism} chargé avec succès', [
                'prism' => $prismName->toString()
            ]);
        } catch (\Throwable $e) {
            $this->databaseConnection->rollback();

            $this->logger->error('Échec du chargement du scénario {prism}: {error}', [
                'prism' => $prismName->toString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw PrismLoadException::fromPrevious($prismName, $e);
        }
    }
}
