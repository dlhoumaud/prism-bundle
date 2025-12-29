<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\Exception;

use Prism\Domain\Exception\ScopePurgeFailedException;
use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ScopePurgeFailedException
 */
final class ScopePurgeFailedExceptionTest extends TestCase
{
    public function testForScopeCreatesExceptionWithFormattedMessage(): void
    {
        $scope = Scope::fromString('dev_alice');
        $previous = new \RuntimeException('Foreign key constraint failed');

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $this->assertInstanceOf(ScopePurgeFailedException::class, $exception);
        $this->assertStringContainsString('dev_alice', $exception->getMessage());
        $this->assertStringContainsString('Foreign key constraint failed', $exception->getMessage());
    }

    public function testForScopeHasCorrectMessage(): void
    {
        $scope = Scope::fromString('dev_bob');
        $previous = new \RuntimeException('Database error');

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $expectedMessage = 'Échec de la purge du scope "dev_bob": Database error';
        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testForScopeChainsPreviousException(): void
    {
        $scope = Scope::fromString('dev_alice');
        $previous = new \RuntimeException('Root cause');

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testForScopeHasZeroCode(): void
    {
        $scope = Scope::fromString('dev_alice');
        $previous = new \RuntimeException('Error', 456);

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $this->assertSame(0, $exception->getCode());
    }

    public function testForScopeWithExceptionSubclass(): void
    {
        $scope = Scope::fromString('dev_alice');
        $previous = new \InvalidArgumentException('Invalid scope data');

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString('Invalid scope data', $exception->getMessage());
    }

    public function testForScopeWithDifferentScope(): void
    {
        $scope = Scope::fromString('test_scope-123');
        $previous = new \RuntimeException('Error');

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $this->assertStringContainsString('test_scope-123', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $scope = Scope::fromString('dev_alice');
        $previous = new \RuntimeException('Error');

        $exception = ScopePurgeFailedException::forScope($scope, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(ScopePurgeFailedException::class);

        $this->assertTrue($reflection->isFinal());
    }
}
