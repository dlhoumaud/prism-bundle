<?php

declare(strict_types=1);

namespace Prism\Application\UseCase;

use Prism\Domain\Contract\DatabaseConnectionInterface;
use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Exception\ScopePurgeFailedException;
use Prism\Domain\ValueObject\Scope;
use Psr\Log\LoggerInterface;

/**
 * Use Case : Purger un scénario fonctionnel
 *
 * Supprime toutes les données créées par un scénario pour un scope donné.
 */
final class PurgePrism
{
    public function __construct(
        private readonly DatabaseConnectionInterface $databaseConnection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(PrismInterface $prism, Scope $scope): void
    {
        $prismName = $prism->getName();

        $this->logger->info('Purge du scénario {prism} pour le scope {scope}', [
            'prism' => $prismName->toString(),
            'scope' => $scope->toString()
        ]);

        try {
            // Note: Multi-database scenarios cannot use transactions
            $this->databaseConnection->beginTransaction();

            $prism->purge($scope);

            $this->databaseConnection->commit();

            $this->logger->info('Scénario {prism} purgé avec succès', [
                'prism' => $prismName->toString()
            ]);
        } catch (\Throwable $e) {
            $this->databaseConnection->rollback();

            $this->logger->error('Échec de la purge du scénario {prism}: {error}', [
                'prism' => $prismName->toString(),
                'error' => $e->getMessage()
            ]);

            throw ScopePurgeFailedException::forScope($scope, $e);
        }
    }
}
