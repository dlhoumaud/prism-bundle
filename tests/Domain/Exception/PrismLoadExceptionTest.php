<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\Exception;

use Prism\Domain\Exception\PrismLoadException;
use Prism\Domain\ValueObject\PrismName;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour PrismLoadException
 */
final class PrismLoadExceptionTest extends TestCase
{
    public function testFromPreviousCreatesExceptionWithFormattedMessage(): void
    {
        $prismName = PrismName::fromString('test_prism');
        $previous = new \RuntimeException('Database connection failed');

        $exception = PrismLoadException::fromPrevious($prismName, $previous);

        $this->assertInstanceOf(PrismLoadException::class, $exception);
        $this->assertStringContainsString('test_prism', $exception->getMessage());
        $this->assertStringContainsString('Database connection failed', $exception->getMessage());
    }

    public function testFromPreviousHasCorrectMessage(): void
    {
        $prismName = PrismName::fromString('my_prism');
        $previous = new \RuntimeException('Something went wrong');

        $exception = PrismLoadException::fromPrevious($prismName, $previous);

        $expectedMessage = 'Échec du chargement du scénario "my_prism": Something went wrong';
        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testFromPreviousChainsPreviousException(): void
    {
        $prismName = PrismName::fromString('test_prism');
        $previous = new \RuntimeException('Root cause');

        $exception = PrismLoadException::fromPrevious($prismName, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testFromPreviousHasZeroCode(): void
    {
        $prismName = PrismName::fromString('test_prism');
        $previous = new \RuntimeException('Error', 123);

        $exception = PrismLoadException::fromPrevious($prismName, $previous);

        $this->assertSame(0, $exception->getCode());
    }

    public function testFromPreviousWithExceptionSubclass(): void
    {
        $prismName = PrismName::fromString('test_prism');
        $previous = new \InvalidArgumentException('Invalid data');

        $exception = PrismLoadException::fromPrevious($prismName, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString('Invalid data', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $prismName = PrismName::fromString('test_prism');
        $previous = new \RuntimeException('Error');

        $exception = PrismLoadException::fromPrevious($prismName, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(PrismLoadException::class);

        $this->assertTrue($reflection->isFinal());
    }
}
