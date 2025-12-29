<?php

declare(strict_types=1);

namespace Prism\Domain\Contract;

use Doctrine\DBAL\Connection;

/**
 * Port : Interface pour l'accès à la connexion base de données
 *
 * Abstraction permettant d'injecter la connexion DBAL dans le domaine
 * tout en respectant l'inversion de dépendance.
 */
interface DatabaseConnectionInterface
{
    /**
     * Retourne la connexion DBAL
     */
    public function getConnection(): Connection;

    /**
     * Démarre une transaction
     */
    public function beginTransaction(): void;

    /**
     * Valide une transaction
     */
    public function commit(): void;

    /**
     * Annule une transaction
     */
    public function rollback(): void;
}
