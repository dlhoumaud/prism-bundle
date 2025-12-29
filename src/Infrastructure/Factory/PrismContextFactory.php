<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Factory;

use Prism\Application\DTO\PrismContext;
use Prism\Domain\Contract\DatabaseConnectionInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\ValueObject\Scope;

/**
 * Factory : Crée des instances de PrismContext
 */
final class PrismContextFactory
{
    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly PrismResourceTrackerInterface $tracker
    ) {
    }

    /**
     * Crée un contexte de scénario avec un scope donné
     */
    public function create(string $scopeValue): PrismContext
    {
        return new PrismContext(
            Scope::fromString($scopeValue),
            $this->connection,
            $this->tracker
        );
    }
}
