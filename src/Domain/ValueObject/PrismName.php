<?php

declare(strict_types=1);

namespace Prism\Domain\ValueObject;

/**
 * Value Object : Nom d'un scénario
 *
 * Représente l'identité unique d'un scénario fonctionnel.
 */
final class PrismName
{
    private function __construct(
        private readonly string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Le nom du scénario ne peut pas être vide');
        }

        if (!preg_match('/^[a-z0-9_\/]+$/', $value)) {
            throw new \InvalidArgumentException(
                'Le nom du scénario doit contenir uniquement des lettres minuscules, chiffres, underscores et slashes (pour les sous-dossiers)'
            );
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
