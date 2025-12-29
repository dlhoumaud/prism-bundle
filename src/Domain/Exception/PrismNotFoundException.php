<?php

declare(strict_types=1);

namespace Prism\Domain\Exception;

use Prism\Domain\ValueObject\PrismName;

/**
 * Exception : Scénario introuvable
 */
final class PrismNotFoundException extends \RuntimeException
{
    public static function forName(PrismName $name): self
    {
        return new self(sprintf('Le scénario "%s" n\'a pas été trouvé', $name->toString()));
    }
}
