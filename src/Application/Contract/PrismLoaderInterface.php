<?php

declare(strict_types=1);

namespace Prism\Application\Contract;

/**
 * Contract pour les chargeurs de scénarios
 *
 * Permet de charger des scénarios depuis différentes sources
 * (YAML, JSON, base de données, etc.)
 */
interface PrismLoaderInterface
{
    /**
     * Charge la définition d'un scénario
     *
     * @return array{
     *     load: array<int, array<string, mixed>>,
     *     purge?: array<int, array<string, mixed>>,
     *     vars?: array<string, mixed>
     * }|null
     */
    public function load(string $prismName): ?array;

    /**
     * Initialise les variables globales pour un scope
     *
     * @param array<string, string> $vars
     */
    public function initializeVariables(array $vars, string $scope): void;

    /**
     * Réinitialise les variables globales
     */
    public function resetVariables(): void;

    /**
     * Démarre un nouveau scope de variables temporaires
     */
    public function startTemporaryScope(): void;

    /**
     * Réinitialise les variables temporaires
     */
    public function resetTemporaryVariables(): void;

    /**
     * Ajoute ou met à jour une variable temporaire
     */
    public function addTemporaryVariable(string $fieldName, mixed $value): void;

    /**
     * Ajoute ou met à jour une variable globale
     */
    public function addVariable(string $name, mixed $value, string $scope): void;

    /**
     * Remplace les placeholders dans les données
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function replacePlaceholders(array $data, string $scope): array;

    /**
     * Convertit une valeur selon son type
     */
    public function convertType(mixed $value, ?string $type = null): mixed;

    /**
     * Retourne le chemin du dossier des scénarios
     */
    public function getDirectory(): string;
}
