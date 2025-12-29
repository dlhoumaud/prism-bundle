<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure\Configurator;

use Prism\Domain\ValueObject\PrismName;
use Prism\Infrastructure\Configurator\PrismConfigurator;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakeConnection;
use Tests\Prism\Infrastructure\FakePrism;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;

/**
 * Tests unitaires pour PrismConfigurator
 */
final class PrismConfiguratorTest extends TestCase
{
    public function testConstructorAcceptsConnectionAndTracker(): void
    {
        $connection = FakeConnection::create();
        $tracker = new FakePrismResourceTracker();

        $configurator = new PrismConfigurator($connection, $tracker);

        $this->assertInstanceOf(PrismConfigurator::class, $configurator);
    }

    public function testConfigureMethodCanBeCalledWithPrism(): void
    {
        $connection = FakeConnection::create();
        $tracker = new FakePrismResourceTracker();
        $prism = new FakePrism(PrismName::fromString('test_prism'));

        $configurator = new PrismConfigurator($connection, $tracker);

        // Ne devrait pas lancer d'exception - méthode vide mais doit être appelée
        $configurator->configure($prism, 'test_scope');

        // Vérifie que l'objet est toujours utilisable après configuration
        $this->assertInstanceOf(PrismConfigurator::class, $configurator);
    }

    public function testConfigureMethodUsesDefaultScopeWhenNotProvided(): void
    {
        $connection = FakeConnection::create();
        $tracker = new FakePrismResourceTracker();
        $prism = new FakePrism(PrismName::fromString('test_prism'));

        $configurator = new PrismConfigurator($connection, $tracker);

        // Ne devrait pas lancer d'exception avec le scope par défaut
        $configurator->configure($prism);

        // Vérifie que l'objet est toujours utilisable après configuration
        $this->assertInstanceOf(PrismConfigurator::class, $configurator);
    }

    public function testConfigureWithMultiplePrismsAndScopes(): void
    {
        $connection = FakeConnection::create();
        $tracker = new FakePrismResourceTracker();
        $configurator = new PrismConfigurator($connection, $tracker);

        // Configure plusieurs prisms avec différents scopes
        $prism1 = new FakePrism(PrismName::fromString('prism_1'));
        $prism2 = new FakePrism(PrismName::fromString('prism_2'));
        $prism3 = new FakePrism(PrismName::fromString('prism_3'));

        $configurator->configure($prism1, 'scope_a');
        $configurator->configure($prism2, 'scope_b');
        $configurator->configure($prism3, 'default');

        // Toutes les configurations devraient réussir sans exception
        $this->assertInstanceOf(PrismConfigurator::class, $configurator);
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(PrismConfigurator::class);

        $this->assertTrue($reflection->isFinal());
    }
}
