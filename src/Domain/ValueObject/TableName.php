<?php

declare(strict_types=1);

namespace Prism\Domain\ValueObject;

/**
 * Value Object : Nom de table
 *
 * Représente le nom d'une table dans la base de données.
 */
final class TableName
{
    private function __construct(
        private readonly string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Le nom de table ne peut pas être vide');
        }

        if (strlen($value) > 64) {
            throw new \InvalidArgumentException('Le nom de table ne peut pas dépasser 64 caractères');
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
