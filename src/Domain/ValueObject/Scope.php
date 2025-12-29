<?php

declare(strict_types=1);

namespace Prism\Domain\ValueObject;

/**
 * Value Object : Scope d'isolation des données
 *
 * Permet d'isoler les données de test créées par différents scénarios
 * ou différentes équipes dans une base de données partagée.
 */
final class Scope
{
    private function __construct(
        private readonly string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Le scope ne peut pas être vide');
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $value)) {
            throw new \InvalidArgumentException(
                'Le scope doit contenir uniquement des lettres minuscules, chiffres, underscores et tirets'
            );
        }

        if (strlen($value) > 50) {
            throw new \InvalidArgumentException('Le scope ne peut pas dépasser 50 caractères');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
