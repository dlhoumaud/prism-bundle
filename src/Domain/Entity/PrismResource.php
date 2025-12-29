<?php

declare(strict_types=1);

namespace Prism\Domain\Entity;

use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\TableName;

/**
 * Entité : Ressource créée par un scénario
 *
 * Représente une ligne de données créée par un scénario dans une table donnée.
 */
final class PrismResource
{
    public function __construct(
        public readonly PrismName $prismName,
        public readonly Scope $scope,
        public readonly TableName $tableName,
        public readonly string $rowId,
        public readonly string $idColumnName,
        public readonly ?string $dbName,
        public readonly \DateTimeImmutable $createdAt
    ) {
    }

    /**
     * Crée une instance depuis un tableau de données
     *
     * @param array{
     *     prism_name: string,
     *     scope: string,
     *     table_name: string,
     *     row_id: string|int,
     *     id_column_name?: string,
     *     db_name?: string|null,
     *     created_at: string|\DateTimeImmutable
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            PrismName::fromString($data['prism_name']),
            Scope::fromString($data['scope']),
            TableName::fromString($data['table_name']),
            (string) $data['row_id'],
            $data['id_column_name'] ?? 'id',
            $data['db_name'] ?? null,
            $data['created_at'] instanceof \DateTimeImmutable
                ? $data['created_at']
                : new \DateTimeImmutable($data['created_at'])
        );
    }

    /**
     * Retourne l'ID réel (stocké en VARCHAR, peut contenir int ou string)
     */
    public function getActualId(): string
    {
        return $this->rowId;
    }
}
