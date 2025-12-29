<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\Exception;

use Prism\Domain\Exception\PrismNotFoundException;
use Prism\Domain\ValueObject\PrismName;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour PrismNotFoundException
 */
final class PrismNotFoundExceptionTest extends TestCase
{
    public function testForNameCreatesExceptionWithFormattedMessage(): void
    {
        $prismName = PrismName::fromString('test_prism');

        $exception = PrismNotFoundException::forName($prismName);

        $this->assertInstanceOf(PrismNotFoundException::class, $exception);
        $this->assertStringContainsString('test_prism', $exception->getMessage());
        $this->assertStringContainsString('pas été trouvé', $exception->getMessage());
    }

    public function testForNameHasCorrectMessage(): void
    {
        $prismName = PrismName::fromString('my_prism');

        $exception = PrismNotFoundException::forName($prismName);

        $expectedMessage = 'Le scénario "my_prism" n\'a pas été trouvé';
        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testForNameWithDifferentPrismName(): void
    {
        $prismName = PrismName::fromString('another_prism');

        $exception = PrismNotFoundException::forName($prismName);

        $this->assertStringContainsString('another_prism', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $prismName = PrismName::fromString('test_prism');

        $exception = PrismNotFoundException::forName($prismName);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionHasNoCodeByDefault(): void
    {
        $prismName = PrismName::fromString('test_prism');

        $exception = PrismNotFoundException::forName($prismName);

        $this->assertSame(0, $exception->getCode());
    }

    public function testExceptionHasNoPreviousByDefault(): void
    {
        $prismName = PrismName::fromString('test_prism');

        $exception = PrismNotFoundException::forName($prismName);

        $this->assertNull($exception->getPrevious());
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(PrismNotFoundException::class);

        $this->assertTrue($reflection->isFinal());
    }
}
