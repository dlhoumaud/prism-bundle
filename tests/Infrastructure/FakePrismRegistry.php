<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Contract\PrismRegistryInterface;
use Prism\Domain\Exception\PrismNotFoundException;
use Prism\Domain\ValueObject\PrismName;

/**
 * Fake Repository : Registry de scénarios pour les tests
 */
final class FakePrismRegistry implements PrismRegistryInterface
{
    /** @var array<string, PrismInterface> */
    private array $prisms = [];

    public function register(PrismInterface $prism): void
    {
        $this->prisms[$prism->getName()->toString()] = $prism;
    }

    public function get(PrismName $name): PrismInterface
    {
        $key = $name->toString();

        if (!isset($this->prisms[$key])) {
            throw PrismNotFoundException::forName($name);
        }

        return $this->prisms[$key];
    }

    public function all(): array
    {
        return array_values($this->prisms);
    }

    public function has(PrismName $name): bool
    {
        return isset($this->prisms[$name->toString()]);
    }

    /**
     * Réinitialise le registry (utile pour les tests)
     */
    public function clear(): void
    {
        $this->prisms = [];
    }
}
