<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\ValueObject;

use Prism\Domain\ValueObject\PrismName;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour PrismName
 */
final class PrismNameTest extends TestCase
{
    public function testFromStringCreatesInstanceWithValidValue(): void
    {
        $name = PrismName::fromString('test_prism');

        $this->assertInstanceOf(PrismName::class, $name);
        $this->assertSame('test_prism', $name->toString());
    }

    public function testFromStringAcceptsLowercaseLetters(): void
    {
        $name = PrismName::fromString('abcdefghijklmnopqrstuvwxyz');

        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $name->toString());
    }

    public function testFromStringAcceptsNumbers(): void
    {
        $name = PrismName::fromString('prism123');

        $this->assertSame('prism123', $name->toString());
    }

    public function testFromStringAcceptsUnderscores(): void
    {
        $name = PrismName::fromString('my_test_prism');

        $this->assertSame('my_test_prism', $name->toString());
    }

    public function testFromStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du scénario ne peut pas être vide');

        PrismName::fromString('');
    }

    public function testFromStringThrowsExceptionForUppercaseLetters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nom du scénario doit contenir uniquement des lettres minuscules, chiffres, underscores et slashes (pour les sous-dossiers)'
        );

        PrismName::fromString('TestPrism');
    }

    public function testFromStringThrowsExceptionForSpaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nom du scénario doit contenir uniquement des lettres minuscules, chiffres, underscores et slashes (pour les sous-dossiers)'
        );

        PrismName::fromString('test prism');
    }

    public function testFromStringThrowsExceptionForHyphens(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nom du scénario doit contenir uniquement des lettres minuscules, chiffres, underscores et slashes (pour les sous-dossiers)'
        );

        PrismName::fromString('test-prism');
    }

    public function testFromStringThrowsExceptionForSpecialCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nom du scénario doit contenir uniquement des lettres minuscules, chiffres, underscores et slashes (pour les sous-dossiers)'
        );

        PrismName::fromString('test@prism');
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $value = 'my_prism_123';
        $name = PrismName::fromString($value);

        $this->assertSame($value, $name->toString());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $name1 = PrismName::fromString('test_prism');
        $name2 = PrismName::fromString('test_prism');

        $this->assertTrue($name1->equals($name2));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $name1 = PrismName::fromString('test_prism');
        $name2 = PrismName::fromString('other_prism');

        $this->assertFalse($name1->equals($name2));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $name = PrismName::fromString('test_prism');

        $this->assertTrue($name->equals($name));
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(PrismName::class);

        $this->assertTrue($reflection->isFinal());
    }
}
