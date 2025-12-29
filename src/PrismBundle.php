<?php

declare(strict_types=1);

namespace Prism;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * PrismBundle
 *
 * Bundle Symfony pour la gestion de scénarios fonctionnels avec isolation multi-scope,
 * traçabilité complète et purge intelligent.
 *
 * @author David Lhoumaud <david.lhoumaud@safti.local>
 */
class PrismBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
