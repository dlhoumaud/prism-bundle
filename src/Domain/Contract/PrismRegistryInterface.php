<?php

declare(strict_types=1);

namespace Prism\Domain\Contract;

use Prism\Domain\ValueObject\PrismName;

/**
 * Port : Registre des scénarios disponibles
 *
 * Permet de récupérer un scénario par son nom.
 */
interface PrismRegistryInterface
{
    /**
     * Récupère un scénario par son nom
     *
     * @param PrismName $name
     * @return PrismInterface|null
     */
    public function get(PrismName $name): ?PrismInterface;

    /**
     * Récupère tous les scénarios disponibles
     *
     * @return PrismInterface[]
     */
    public function all(): array;

    /**
     * Vérifie si un scénario existe
     *
     * @param PrismName $name
     * @return bool
     */
    public function has(PrismName $name): bool;
}
