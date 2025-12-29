<?php

declare(strict_types=1);

namespace Prism\Domain\Exception;

use Prism\Domain\ValueObject\PrismName;

/**
 * Exception : Échec du chargement d'un scénario
 */
final class PrismLoadException extends \RuntimeException
{
    public static function fromPrevious(PrismName $prismName, \Throwable $previous): self
    {
        return new self(
            sprintf(
                'Échec du chargement du scénario "%s": %s',
                $prismName->toString(),
                $previous->getMessage()
            ),
            0,
            $previous
        );
    }
}
