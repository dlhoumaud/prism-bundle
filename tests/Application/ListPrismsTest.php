<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\UseCase\ListPrisms;
use Prism\Domain\ValueObject\PrismName;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakePrism;
use Tests\Prism\Infrastructure\FakePrismRegistry;

/**
 * Tests unitaires : ListPrisms Use Case
 */
final class ListPrismsTest extends TestCase
{
    private FakePrismRegistry $registry;
    private ListPrisms $useCase;

    protected function setUp(): void
    {
        $this->registry = new FakePrismRegistry();
        $this->useCase = new ListPrisms($this->registry);
    }

    public function testExecuteShouldReturnEmptyArrayWhenNoPrisms(): void
    {
        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertIsArray($prisms);
        $this->assertCount(0, $prisms, 'Aucun scénario ne devrait être retourné');
    }

    public function testExecuteShouldReturnSinglePrism(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $prism = new FakePrism($prismName);
        $this->registry->register($prism);

        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertCount(1, $prisms, 'Un scénario devrait être retourné');
        $this->assertSame($prism, $prisms[0], 'Le scénario devrait correspondre');
    }

    public function testExecuteShouldReturnMultiplePrisms(): void
    {
        // Arrange
        $prism1 = new FakePrism(PrismName::fromString('prism_1'));
        $prism2 = new FakePrism(PrismName::fromString('prism_2'));
        $prism3 = new FakePrism(PrismName::fromString('prism_3'));

        $this->registry->register($prism1);
        $this->registry->register($prism2);
        $this->registry->register($prism3);

        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertCount(3, $prisms, 'Trois scénarios devraient être retournés');
        $this->assertContains($prism1, $prisms);
        $this->assertContains($prism2, $prisms);
        $this->assertContains($prism3, $prisms);
    }

    public function testExecuteShouldReturnPrismsInOrder(): void
    {
        // Arrange
        $prism1 = new FakePrism(PrismName::fromString('alpha'));
        $prism2 = new FakePrism(PrismName::fromString('beta'));
        $prism3 = new FakePrism(PrismName::fromString('gamma'));

        $this->registry->register($prism1);
        $this->registry->register($prism2);
        $this->registry->register($prism3);

        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertCount(3, $prisms);
        $this->assertSame($prism1, $prisms[0]);
        $this->assertSame($prism2, $prisms[1]);
        $this->assertSame($prism3, $prisms[2]);
    }

    public function testExecuteShouldNotModifyRegistry(): void
    {
        // Arrange
        $prism = new FakePrism(PrismName::fromString('test_prism'));
        $this->registry->register($prism);

        // Act
        $prismsBefore = $this->useCase->execute();
        $prismsAfter = $this->useCase->execute();

        // Assert
        $this->assertCount(1, $prismsBefore);
        $this->assertCount(1, $prismsAfter);
        $this->assertSame($prism, $prismsBefore[0]);
        $this->assertSame($prism, $prismsAfter[0]);
    }

    public function testExecuteShouldHandleRegistryChangesBetweenCalls(): void
    {
        // Arrange
        $prism1 = new FakePrism(PrismName::fromString('prism_1'));
        $this->registry->register($prism1);

        // Act - Premier appel
        $prisms1 = $this->useCase->execute();

        // Arrange - Ajout d'un scénario
        $prism2 = new FakePrism(PrismName::fromString('prism_2'));
        $this->registry->register($prism2);

        // Act - Deuxième appel
        $prisms2 = $this->useCase->execute();

        // Assert
        $this->assertCount(1, $prisms1, 'Premier appel devrait retourner 1 scénario');
        $this->assertCount(2, $prisms2, 'Deuxième appel devrait retourner 2 scénarios');
    }

    public function testExecuteShouldReturnPrismInterfaces(): void
    {
        // Arrange
        $prism = new FakePrism(PrismName::fromString('test_prism'));
        $this->registry->register($prism);

        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertCount(1, $prisms);
        $this->assertInstanceOf(
            \Prism\Domain\Contract\PrismInterface::class,
            $prisms[0],
            'Le scénario devrait implémenter PrismInterface'
        );
    }

    public function testExecuteWithTenPrisms(): void
    {
        // Arrange
        for ($i = 1; $i <= 10; $i++) {
            $prism = new FakePrism(PrismName::fromString('prism_' . $i));
            $this->registry->register($prism);
        }

        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertCount(10, $prisms, 'Dix scénarios devraient être retournés');
    }

    public function testExecuteShouldHandlePrismsWithSimilarNames(): void
    {
        // Arrange
        $prism1 = new FakePrism(PrismName::fromString('test_prism'));
        $prism2 = new FakePrism(PrismName::fromString('test_prism_2'));
        $prism3 = new FakePrism(PrismName::fromString('test_prism_advanced'));

        $this->registry->register($prism1);
        $this->registry->register($prism2);
        $this->registry->register($prism3);

        // Act
        $prisms = $this->useCase->execute();

        // Assert
        $this->assertCount(3, $prisms, 'Les trois scénarios devraient être distincts');
        $this->assertNotSame($prism1, $prism2);
        $this->assertNotSame($prism1, $prism3);
        $this->assertNotSame($prism2, $prism3);
    }
}
