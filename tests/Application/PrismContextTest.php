<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\DTO\PrismContext;
use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakeDatabaseConnection;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;

/**
 * Tests unitaires : PrismContext DTO
 */
final class PrismContextTest extends TestCase
{
    public function testConstructShouldStoreAllProperties(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $connection = new FakeDatabaseConnection();
        $tracker = new FakePrismResourceTracker();

        // Act
        $context = new PrismContext($scope, $connection, $tracker);

        // Assert
        $this->assertSame($scope, $context->scope);
        $this->assertSame($connection, $context->connection);
        $this->assertSame($tracker, $context->tracker);
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $connection = new FakeDatabaseConnection();
        $tracker = new FakePrismResourceTracker();
        $context = new PrismContext($scope, $connection, $tracker);

        // Assert - Les propriétés readonly ne peuvent pas être modifiées
        $this->expectException(\Error::class);

        // Act - Tentative de modification via Reflection pour contourner l'analyse statique
        $reflection = new \ReflectionProperty(PrismContext::class, 'scope');
        $reflection->setValue($context, Scope::fromString('other_scope'));
    }

    public function testDifferentScopesCreateDifferentContexts(): void
    {
        // Arrange
        $scope1 = Scope::fromString('scope_1');
        $scope2 = Scope::fromString('scope_2');
        $connection = new FakeDatabaseConnection();
        $tracker = new FakePrismResourceTracker();

        // Act
        $context1 = new PrismContext($scope1, $connection, $tracker);
        $context2 = new PrismContext($scope2, $connection, $tracker);

        // Assert
        $this->assertNotSame($context1->scope, $context2->scope);
        $this->assertFalse($context1->scope->equals($context2->scope));
    }
}
