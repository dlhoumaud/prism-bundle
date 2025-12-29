<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;

/**
 * Fake Repository : Scénario pour les tests
 */
final class FakePrism implements PrismInterface
{
    private int $loadCallCount = 0;
    private int $purgeCallCount = 0;
    private bool $shouldFailOnLoad = false;
    private bool $shouldFailOnPurge = false;

    /** @var Scope[] */
    private array $loadedScopes = [];

    /** @var Scope[] */
    private array $purgedScopes = [];

    public function __construct(
        private readonly PrismName $name
    ) {
    }

    public function getName(): PrismName
    {
        return $this->name;
    }

    public function load(Scope $scope): void
    {
        if ($this->shouldFailOnLoad) {
            throw new \RuntimeException('Simulated load failure');
        }

        $this->loadCallCount++;
        $this->loadedScopes[] = $scope;
    }

    public function purge(Scope $scope): void
    {
        if ($this->shouldFailOnPurge) {
            throw new \RuntimeException('Simulated purge failure');
        }

        $this->purgeCallCount++;
        $this->purgedScopes[] = $scope;
    }

    public function getLoadCallCount(): int
    {
        return $this->loadCallCount;
    }

    public function getPurgeCallCount(): int
    {
        return $this->purgeCallCount;
    }

    public function getLoadedScopes(): array
    {
        return $this->loadedScopes;
    }

    public function getPurgedScopes(): array
    {
        return $this->purgedScopes;
    }

    public function simulateLoadFailure(): void
    {
        $this->shouldFailOnLoad = true;
    }

    public function simulatePurgeFailure(): void
    {
        $this->shouldFailOnPurge = true;
    }

    public function reset(): void
    {
        $this->loadCallCount = 0;
        $this->purgeCallCount = 0;
        $this->loadedScopes = [];
        $this->purgedScopes = [];
        $this->shouldFailOnLoad = false;
        $this->shouldFailOnPurge = false;
    }
}
