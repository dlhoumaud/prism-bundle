<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\ValueObject;

use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour Scope
 */
final class ScopeTest extends TestCase
{
    public function testFromStringCreatesInstanceWithValidValue(): void
    {
        $scope = Scope::fromString('dev_alice');

        $this->assertInstanceOf(Scope::class, $scope);
        $this->assertSame('dev_alice', $scope->toString());
    }

    public function testFromStringAcceptsLowercaseLetters(): void
    {
        $scope = Scope::fromString('abcdefghijklmnopqrstuvwxyz');

        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $scope->toString());
    }

    public function testFromStringAcceptsNumbers(): void
    {
        $scope = Scope::fromString('scope123');

        $this->assertSame('scope123', $scope->toString());
    }

    public function testFromStringAcceptsUnderscores(): void
    {
        $scope = Scope::fromString('dev_alice_test');

        $this->assertSame('dev_alice_test', $scope->toString());
    }

    public function testFromStringAcceptsHyphens(): void
    {
        $scope = Scope::fromString('dev-alice-test');

        $this->assertSame('dev-alice-test', $scope->toString());
    }

    public function testFromStringAcceptsMixedValidCharacters(): void
    {
        $scope = Scope::fromString('dev_alice-123');

        $this->assertSame('dev_alice-123', $scope->toString());
    }

    public function testFromStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le scope ne peut pas être vide');

        Scope::fromString('');
    }

    public function testFromStringThrowsExceptionForUppercaseLetters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le scope doit contenir uniquement des lettres minuscules, chiffres, underscores et tirets'
        );

        Scope::fromString('DevAlice');
    }

    public function testFromStringThrowsExceptionForSpaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le scope doit contenir uniquement des lettres minuscules, chiffres, underscores et tirets'
        );

        Scope::fromString('dev alice');
    }

    public function testFromStringThrowsExceptionForSpecialCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le scope doit contenir uniquement des lettres minuscules, chiffres, underscores et tirets'
        );

        Scope::fromString('dev@alice');
    }

    public function testFromStringThrowsExceptionForDots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le scope doit contenir uniquement des lettres minuscules, chiffres, underscores et tirets'
        );

        Scope::fromString('dev.alice');
    }

    public function testFromStringThrowsExceptionForStringLongerThan50Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le scope ne peut pas dépasser 50 caractères');

        Scope::fromString(str_repeat('a', 51));
    }

    public function testFromStringAcceptsStringWith50Characters(): void
    {
        $value = str_repeat('a', 50);
        $scope = Scope::fromString($value);

        $this->assertSame($value, $scope->toString());
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $value = 'dev_alice-123';
        $scope = Scope::fromString($value);

        $this->assertSame($value, $scope->toString());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $scope1 = Scope::fromString('dev_alice');
        $scope2 = Scope::fromString('dev_alice');

        $this->assertTrue($scope1->equals($scope2));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $scope1 = Scope::fromString('dev_alice');
        $scope2 = Scope::fromString('dev_bob');

        $this->assertFalse($scope1->equals($scope2));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $scope = Scope::fromString('dev_alice');

        $this->assertTrue($scope->equals($scope));
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(Scope::class);

        $this->assertTrue($reflection->isFinal());
    }
}
