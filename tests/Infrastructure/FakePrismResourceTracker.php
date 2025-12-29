<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\Entity\PrismResource;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\TableName;

/**
 * Fake Repository : Tracker de ressources pour les tests
 */
final class FakePrismResourceTracker implements PrismResourceTrackerInterface
{
    /** @var PrismResource[] */
    private array $resources = [];

    private int $trackCallCount = 0;
    private int $purgeCallCount = 0;

    public function track(
        PrismName $prismName,
        Scope $scope,
        TableName $tableName,
        string|int $rowId,
        string $idColumnName = 'id',
        ?string $dbName = null
    ): void {
        $this->resources[] = new PrismResource(
            $prismName,
            $scope,
            $tableName,
            (string) $rowId,
            $idColumnName,
            $dbName,
            new \DateTimeImmutable()
        );

        $this->trackCallCount++;
    }

    public function findByPrismAndScope(PrismName $prismName, Scope $scope): array
    {
        return array_values(array_filter(
            $this->resources,
            fn(PrismResource $resource) =>
                $resource->prismName->equals($prismName) &&
                $resource->scope->equals($scope)
        ));
    }

    public function deleteByPrismAndScope(PrismName $prismName, Scope $scope): void
    {
        $this->resources = array_values(array_filter(
            $this->resources,
            fn(PrismResource $resource) =>
                !($resource->prismName->equals($prismName) && $resource->scope->equals($scope))
        ));

        $this->purgeCallCount++;
    }

    public function deleteByScope(Scope $scope): void
    {
        $this->resources = array_values(array_filter(
            $this->resources,
            fn(PrismResource $resource) => !$resource->scope->equals($scope)
        ));
    }

    public function purge(PrismName $prismName, Scope $scope): void
    {
        $this->deleteByPrismAndScope($prismName, $scope);
    }

    public function getTrackCallCount(): int
    {
        return $this->trackCallCount;
    }

    public function getPurgeCallCount(): int
    {
        return $this->purgeCallCount;
    }

    public function getAllResources(): array
    {
        return $this->resources;
    }

    public function reset(): void
    {
        $this->resources = [];
        $this->trackCallCount = 0;
        $this->purgeCallCount = 0;
    }
}
