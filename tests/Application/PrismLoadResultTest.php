<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\DTO\PrismLoadResult;
use Prism\Domain\ValueObject\PrismName;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires : PrismLoadResult DTO
 */
final class PrismLoadResultTest extends TestCase
{
    public function testSuccessShouldCreateSuccessfulResult(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $resourcesCreated = 42;
        $executionTimeMs = 123.45;

        // Act
        $result = PrismLoadResult::success($prismName, $resourcesCreated, $executionTimeMs);

        // Assert
        $this->assertTrue($result->success, 'Le résultat devrait être un succès');
        $this->assertSame($prismName, $result->prismName);
        $this->assertSame(42, $result->resourcesCreated);
        $this->assertSame(123.45, $result->executionTimeMs);
        $this->assertNull($result->errorMessage, 'Aucun message d\'erreur ne devrait être présent');
    }

    public function testFailureShouldCreateFailedResult(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $errorMessage = 'Database connection failed';
        $executionTimeMs = 50.25;

        // Act
        $result = PrismLoadResult::failure($prismName, $errorMessage, $executionTimeMs);

        // Assert
        $this->assertFalse($result->success, 'Le résultat devrait être un échec');
        $this->assertSame($prismName, $result->prismName);
        $this->assertSame(0, $result->resourcesCreated, 'Aucune ressource ne devrait être créée en cas d\'échec');
        $this->assertSame(50.25, $result->executionTimeMs);
        $this->assertSame('Database connection failed', $result->errorMessage);
    }

    public function testSuccessWithZeroResources(): void
    {
        // Arrange
        $prismName = PrismName::fromString('empty_prism');

        // Act
        $result = PrismLoadResult::success($prismName, 0, 10.5);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame(0, $result->resourcesCreated);
    }

    public function testSuccessWithLargeNumberOfResources(): void
    {
        // Arrange
        $prismName = PrismName::fromString('massive_prism');
        $resourcesCreated = 999999;

        // Act
        $result = PrismLoadResult::success($prismName, $resourcesCreated, 5000.0);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame(999999, $result->resourcesCreated);
    }

    public function testFailureWithEmptyErrorMessage(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');

        // Act
        $result = PrismLoadResult::failure($prismName, '', 10.0);

        // Assert
        $this->assertFalse($result->success);
        $this->assertSame('', $result->errorMessage);
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $result = PrismLoadResult::success($prismName, 10, 50.0);

        // Assert
        $this->expectException(\Error::class);

        // Act - Tentative de modification via Reflection pour contourner l'analyse statique
        $reflection = new \ReflectionProperty(PrismLoadResult::class, 'success');
        $reflection->setValue($result, false);
    }

    public function testDifferentExecutionTimes(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');

        // Act
        $fastResult = PrismLoadResult::success($prismName, 5, 0.5);
        $slowResult = PrismLoadResult::success($prismName, 5, 10000.0);

        // Assert
        $this->assertSame(0.5, $fastResult->executionTimeMs);
        $this->assertSame(10000.0, $slowResult->executionTimeMs);
    }
}
