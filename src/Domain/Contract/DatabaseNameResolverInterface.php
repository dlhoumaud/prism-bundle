<?php

declare(strict_types=1);

namespace Prism\Domain\Contract;

/**
 * Résout les noms de connexion Doctrine vers les noms de bases de données
 */
interface DatabaseNameResolverInterface
{
    /**
     * Résout un nom de base de données
     *
     * Si le format est %connection_name%, extrait le nom réel depuis Doctrine
     * Sinon retourne le nom tel quel
     *
     * @param string|null $dbName Nom de base ou %connection_name%
     * @return string|null Nom de base résolu ou null
     */
    public function resolve(?string $dbName): ?string;
}
