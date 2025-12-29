<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Domain\Contract\DatabaseNameResolverInterface;

/**
 * Fake Repository : Résolveur de noms de base de données pour les tests
 */
final class FakeDatabaseNameResolver implements DatabaseNameResolverInterface
{
    public function resolve(?string $dbName): ?string
    {
        // Dans les tests, on retourne le nom tel quel
        return $dbName;
    }
}
