<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure\Factory;

use Prism\Application\DTO\PrismContext;
use Prism\Infrastructure\Factory\PrismContextFactory;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakeConnection;
use Tests\Prism\Infrastructure\FakeDatabaseConnection;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;

/**
 * Tests unitaires pour PrismContextFactory
 */
final class PrismContextFactoryTest extends TestCase
{
    public function testCreateReturnsPrismContextWithCorrectScope(): void
    {
        $connection = new FakeDatabaseConnection(FakeConnection::create());
        $tracker = new FakePrismResourceTracker();

        $factory = new PrismContextFactory($connection, $tracker);

        $context = $factory->create('dev_alice');

        $this->assertInstanceOf(PrismContext::class, $context);
        $this->assertSame('dev_alice', $context->scope->toString());
    }

    public function testCreateInjectsConnectionIntoContext(): void
    {
        $connection = new FakeDatabaseConnection(FakeConnection::create());
        $tracker = new FakePrismResourceTracker();

        $factory = new PrismContextFactory($connection, $tracker);

        $context = $factory->create('test_scope');

        $this->assertSame($connection, $context->connection);
    }

    public function testCreateInjectsTrackerIntoContext(): void
    {
        $connection = new FakeDatabaseConnection(FakeConnection::create());
        $tracker = new FakePrismResourceTracker();

        $factory = new PrismContextFactory($connection, $tracker);

        $context = $factory->create('test_scope');

        $this->assertSame($tracker, $context->tracker);
    }

    public function testCreateWithDifferentScopes(): void
    {
        $connection = new FakeDatabaseConnection(FakeConnection::create());
        $tracker = new FakePrismResourceTracker();

        $factory = new PrismContextFactory($connection, $tracker);

        $context1 = $factory->create('scope_1');
        $context2 = $factory->create('scope_2');

        $this->assertSame('scope_1', $context1->scope->toString());
        $this->assertSame('scope_2', $context2->scope->toString());
        $this->assertFalse($context1->scope->equals($context2->scope));
    }

    public function testCreateThrowsExceptionForInvalidScope(): void
    {
        $connection = new FakeDatabaseConnection(FakeConnection::create());
        $tracker = new FakePrismResourceTracker();

        $factory = new PrismContextFactory($connection, $tracker);

        $this->expectException(\InvalidArgumentException::class);

        $factory->create('Invalid Scope');
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(PrismContextFactory::class);

        $this->assertTrue($reflection->isFinal());
    }
}
