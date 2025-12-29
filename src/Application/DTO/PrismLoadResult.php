<?php

declare(strict_types=1);

namespace Prism\Application\DTO;

use Prism\Domain\ValueObject\PrismName;

/**
 * DTO : Résultat du chargement d'un scénario
 */
final class PrismLoadResult
{
    public function __construct(
        public readonly PrismName $prismName,
        public readonly int $resourcesCreated,
        public readonly float $executionTimeMs,
        public readonly bool $success,
        public readonly ?string $errorMessage = null
    ) {
    }

    public static function success(
        PrismName $prismName,
        int $resourcesCreated,
        float $executionTimeMs
    ): self {
        return new self($prismName, $resourcesCreated, $executionTimeMs, true);
    }

    public static function failure(
        PrismName $prismName,
        string $errorMessage,
        float $executionTimeMs
    ): self {
        return new self($prismName, 0, $executionTimeMs, false, $errorMessage);
    }
}
