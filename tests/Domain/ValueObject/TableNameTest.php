<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\ValueObject;

use Prism\Domain\ValueObject\TableName;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour TableName
 */
final class TableNameTest extends TestCase
{
    public function testFromStringCreatesInstanceWithValidValue(): void
    {
        $tableName = TableName::fromString('users');

        $this->assertInstanceOf(TableName::class, $tableName);
        $this->assertSame('users', $tableName->toString());
    }

    public function testFromStringAcceptsLowercaseLetters(): void
    {
        $tableName = TableName::fromString('my_table');

        $this->assertSame('my_table', $tableName->toString());
    }

    public function testFromStringAcceptsUppercaseLetters(): void
    {
        $tableName = TableName::fromString('MyTable');

        $this->assertSame('MyTable', $tableName->toString());
    }

    public function testFromStringAcceptsNumbers(): void
    {
        $tableName = TableName::fromString('table123');

        $this->assertSame('table123', $tableName->toString());
    }

    public function testFromStringAcceptsUnderscores(): void
    {
        $tableName = TableName::fromString('my_test_table');

        $this->assertSame('my_test_table', $tableName->toString());
    }

    public function testFromStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de table ne peut pas être vide');

        TableName::fromString('');
    }

    public function testFromStringThrowsExceptionForStringLongerThan64Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de table ne peut pas dépasser 64 caractères');

        TableName::fromString(str_repeat('a', 65));
    }

    public function testFromStringAcceptsStringWith64Characters(): void
    {
        $value = str_repeat('a', 64);
        $tableName = TableName::fromString($value);

        $this->assertSame($value, $tableName->toString());
    }

    public function testFromStringAcceptsStringWith63Characters(): void
    {
        $value = str_repeat('a', 63);
        $tableName = TableName::fromString($value);

        $this->assertSame($value, $tableName->toString());
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $value = 'my_database_table';
        $tableName = TableName::fromString($value);

        $this->assertSame($value, $tableName->toString());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $tableName1 = TableName::fromString('users');
        $tableName2 = TableName::fromString('users');

        $this->assertTrue($tableName1->equals($tableName2));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $tableName1 = TableName::fromString('users');
        $tableName2 = TableName::fromString('posts');

        $this->assertFalse($tableName1->equals($tableName2));
    }

    public function testEqualsReturnsTrueForSameInstance(): void
    {
        $tableName = TableName::fromString('users');

        $this->assertTrue($tableName->equals($tableName));
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(TableName::class);

        $this->assertTrue($reflection->isFinal());
    }
}
