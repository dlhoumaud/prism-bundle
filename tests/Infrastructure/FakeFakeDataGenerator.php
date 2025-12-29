<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Domain\Contract\FakeDataGeneratorInterface;

/**
 * Fake FakeDataGenerator pour les tests
 */
final class FakeFakeDataGenerator implements FakeDataGeneratorInterface
{
    /** @var array<string, string|int> */
    private array $fixedValues = [];

    /**
     * Définit une valeur fixe pour un type donné
     */
    public function setFixedValue(string $type, string|int $value): void
    {
        $this->fixedValues[$type] = $value;
    }

    /**
     * Génère des données fake prévisibles pour les tests
     */
    public function generate(string $type, ?string $scope = null, mixed ...$params): string|int
    {
        // Si une valeur fixe est définie, la retourner
        if (isset($this->fixedValues[$type])) {
            return $this->fixedValues[$type];
        }

        // Génération prévisible pour les tests
        $scopeSuffix = $scope !== null ? "_{$scope}" : '';

        return match ($type) {
            'user', 'username' => "fake_user{$scopeSuffix}",
            'email' => "fake_user{$scopeSuffix}@test.fake",
            'firstname' => 'FakeFirstname',
            'lastname' => $scope !== null ? "FakeLastname ({$scope})" : 'FakeLastname',
            'company' => 'Fake Corp',
            'id' => 42,
            'uuid' => 'fake-uuid-1234-5678-abcd',
            'date' => '2025-01-01',
            'datetime' => '2025-01-01 12:00:00',
            'pathfile' => '/tmp/fake_file.txt',
            'pathdir' => '/tmp/fake_dir',
            'text' => 'fake text content',
            'number' => 99,
            'url' => 'https://fake.test',
            'ip' => '192.168.1.1',
            'ipv6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'mac' => 'AA:BB:CC:DD:EE:FF',
            'tel', 'phone' => '+33123456789',
            'color' => '#abcdef',
            'boolean' => 1,
            default => throw new \InvalidArgumentException(sprintf('Unknown fake type: %s', $type))
        };
    }
}
