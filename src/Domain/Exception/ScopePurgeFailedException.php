<?php

declare(strict_types=1);

namespace Prism\Domain\Exception;

use Prism\Domain\ValueObject\Scope;

/**
 * Exception : Échec de purge d'un scope
 */
final class ScopePurgeFailedException extends \RuntimeException
{
    public static function forScope(Scope $scope, \Throwable $previous): self
    {
        return new self(
            sprintf('Échec de la purge du scope "%s": %s', $scope->toString(), $previous->getMessage()),
            0,
            $previous
        );
    }
}
