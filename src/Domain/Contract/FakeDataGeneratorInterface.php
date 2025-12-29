<?php

declare(strict_types=1);

namespace Prism\Domain\Contract;

/**
 * Port : Générateur de données aléatoires
 *
 * Permet de générer des données de test aléatoires mais cohérentes
 * pour alimenter les scénarios fonctionnels.
 */
interface FakeDataGeneratorInterface
{
    /**
     * Génère une donnée aléatoire du type spécifié
     *
     * @param string $type Type de donnée (user, email, tel, etc.)
     * @param string|null $scope Scope optionnel pour inclure dans les données générées
     * @param mixed ...$params Paramètres additionnels selon le type
     * @return string|int|float|bool Donnée générée
     */
    public function generate(string $type, ?string $scope = null, mixed ...$params): string|int|float|bool;
}
