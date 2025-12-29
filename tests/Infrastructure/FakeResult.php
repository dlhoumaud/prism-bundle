<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Doctrine\DBAL\Driver\Result;

/**
 * Fake Repository : Résultat Doctrine DBAL pour les tests
 */
final class FakeResult implements Result
{
    private array $data = [];
    private int $position = 0;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function fetchNumeric(): array|false
    {
        if ($this->position >= count($this->data)) {
            return false;
        }

        return array_values($this->data[$this->position++]);
    }

    public function fetchAssociative(): array|false
    {
        if ($this->position >= count($this->data)) {
            return false;
        }

        return $this->data[$this->position++];
    }

    public function fetchOne(): mixed
    {
        $row = $this->fetchNumeric();
        return $row[0] ?? false;
    }

    public function fetchAllNumeric(): array
    {
        $result = [];
        while (($row = $this->fetchNumeric()) !== false) {
            $result[] = $row;
        }
        return $result;
    }

    public function fetchAllAssociative(): array
    {
        $result = [];
        while (($row = $this->fetchAssociative()) !== false) {
            $result[] = $row;
        }
        return $result;
    }

    public function fetchFirstColumn(): array
    {
        $result = [];
        while (($row = $this->fetchOne()) !== false) {
            $result[] = $row;
        }
        return $result;
    }

    public function rowCount(): int
    {
        return count($this->data);
    }

    public function columnCount(): int
    {
        return empty($this->data) ? 0 : count($this->data[0]);
    }

    public function free(): void
    {
        $this->data = [];
        $this->position = 0;
    }
}
