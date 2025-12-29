<?php

declare(strict_types=1);

namespace Prism\Application\UseCase;

use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Contract\PrismRegistryInterface;

/**
 * Use Case : Lister tous les scénarios disponibles
 */
final class ListPrisms
{
    public function __construct(
        private readonly PrismRegistryInterface $prismRegistry
    ) {
    }

    /**
     * @return PrismInterface[]
     */
    public function execute(): array
    {
        return $this->prismRegistry->all();
    }
}
