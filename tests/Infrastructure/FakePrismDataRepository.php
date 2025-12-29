<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Application\Contract\PrismDataRepositoryInterface;

/**
 * Fake Repository : Repository de données pour les tests
 */
class FakePrismDataRepository implements PrismDataRepositoryInterface
{
    private int $nextId = 1;
    private array $insertedData = [];
    private array $executedStatements = [];
    private array $queryResults = [];

    public function insert(string $tableName, array $data, array $types = []): int|string|null
    {
        // Si un ID est fourni dans data, l'utiliser
        if (isset($data['id'])) {
            $id = $data['id'];
        } else {
            $id = $this->nextId++;
        }

        $this->insertedData[] = [
            'table' => $tableName,
            'data' => $data,
            'types' => $types,
            'id' => $id,
        ];

        /** @var int|string|null */
        return $id;
    }

    public function delete(string $tableName, array $where): int
    {
        $deleted = 0;

        foreach ($this->insertedData as $key => $row) {
            if ($row['table'] !== $tableName) {
                continue;
            }

            $match = true;
            foreach ($where as $column => $value) {
                if (!isset($row['data'][$column]) || $row['data'][$column] !== $value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                unset($this->insertedData[$key]);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function executeStatement(string $sql, array $params = []): int
    {
        $this->executedStatements[] = [
            'sql' => $sql,
            'params' => $params,
        ];

        // Simuler une suppression réussie
        return 1;
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        // Retourner les résultats prédéfinis
        return $this->queryResults;
    }

    /**
     * Définit les résultats à retourner pour executeQuery
     */
    public function setQueryResults(array $results): void
    {
        $this->queryResults = $results;
    }

    /**
     * Définit le mode de retour invalide (pour tester les exceptions de type)
     */
    public function setQueryResultsWithInvalidType(): void
    {
        $this->queryResults = [
            ['id' => ['invalid' => 'array']] // Type invalide (array au lieu de int|string)
        ];
    }

    public function getInsertedData(): array
    {
        return array_values($this->insertedData);
    }

    public function getLastInsertedData(): array
    {
        if (empty($this->insertedData)) {
            return [];
        }

        $lastInserted = end($this->insertedData);
        return $lastInserted['data'];
    }

    public function getExecutedStatements(): array
    {
        return $this->executedStatements;
    }

    public function reset(): void
    {
        $this->nextId = 1;
        $this->insertedData = [];
        $this->executedStatements = [];
        $this->queryResults = [];
    }
}
