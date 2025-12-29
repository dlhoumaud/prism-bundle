<?php

declare(strict_types=1);

namespace Tests\Prism\Domain\Entity;

use Prism\Domain\Entity\PrismResource;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\TableName;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour PrismResource
 */
final class PrismResourceTest extends TestCase
{
    public function testConstructorCreatesInstanceWithAllProperties(): void
    {
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('dev_alice');
        $tableName = TableName::fromString('users');
        $rowId = '123';
        $idColumnName = 'id';
        $createdAt = new \DateTimeImmutable('2025-01-01 10:00:00');

        $resource = new PrismResource(
            $prismName,
            $scope,
            $tableName,
            $rowId,
            $idColumnName,
            null,
            $createdAt
        );

        $this->assertSame($prismName, $resource->prismName);
        $this->assertSame($scope, $resource->scope);
        $this->assertSame($tableName, $resource->tableName);
        $this->assertSame($rowId, $resource->rowId);
        $this->assertSame($idColumnName, $resource->idColumnName);
        $this->assertSame($createdAt, $resource->createdAt);
    }

    public function testFromArrayCreatesInstanceFromArrayData(): void
    {
        $data = [
            'prism_name' => 'test_prism',
            'scope' => 'dev_alice',
            'table_name' => 'users',
            'row_id' => '123',
            'id_column_name' => 'id',
            'created_at' => '2025-01-01 10:00:00'
        ];

        $resource = PrismResource::fromArray($data);

        $this->assertSame('test_prism', $resource->prismName->toString());
        $this->assertSame('dev_alice', $resource->scope->toString());
        $this->assertSame('users', $resource->tableName->toString());
        $this->assertSame('123', $resource->rowId);
        $this->assertSame('id', $resource->idColumnName);
        $this->assertInstanceOf(\DateTimeImmutable::class, $resource->createdAt);
        $this->assertSame('2025-01-01 10:00:00', $resource->createdAt->format('Y-m-d H:i:s'));
    }

    public function testFromArrayWithNumericRowId(): void
    {
        $data = [
            'prism_name' => 'test_prism',
            'scope' => 'dev_alice',
            'table_name' => 'users',
            'row_id' => 123,
            'id_column_name' => 'id',
            'created_at' => '2025-01-01 10:00:00'
        ];

        $resource = PrismResource::fromArray($data);

        $this->assertSame('123', $resource->rowId);
    }

    public function testFromArrayWithDateTimeImmutableCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-01 10:00:00');
        $data = [
            'prism_name' => 'test_prism',
            'scope' => 'dev_alice',
            'table_name' => 'users',
            'row_id' => '123',
            'id_column_name' => 'id',
            'created_at' => $createdAt
        ];

        $resource = PrismResource::fromArray($data);

        $this->assertSame($createdAt, $resource->createdAt);
    }

    public function testFromArrayUsesDefaultIdColumnNameWhenNotProvided(): void
    {
        $data = [
            'prism_name' => 'test_prism',
            'scope' => 'dev_alice',
            'table_name' => 'users',
            'row_id' => '123',
            'created_at' => '2025-01-01 10:00:00'
        ];

        $resource = PrismResource::fromArray($data);

        $this->assertSame('id', $resource->idColumnName);
    }

    public function testFromArrayWithCustomIdColumnName(): void
    {
        $data = [
            'prism_name' => 'test_prism',
            'scope' => 'dev_alice',
            'table_name' => 'users',
            'row_id' => 'abc-123',
            'id_column_name' => 'uuid',
            'created_at' => '2025-01-01 10:00:00'
        ];

        $resource = PrismResource::fromArray($data);

        $this->assertSame('uuid', $resource->idColumnName);
    }

    public function testGetActualIdReturnsRowId(): void
    {
        $resource = new PrismResource(
            PrismName::fromString('test_prism'),
            Scope::fromString('dev_alice'),
            TableName::fromString('users'),
            '123',
            'id',
            null,
            new \DateTimeImmutable()
        );

        $this->assertSame('123', $resource->getActualId());
    }

    public function testGetActualIdReturnsStringIdForNumericValue(): void
    {
        $resource = new PrismResource(
            PrismName::fromString('test_prism'),
            Scope::fromString('dev_alice'),
            TableName::fromString('users'),
            '456',
            'id',
            null,
            new \DateTimeImmutable()
        );

        $this->assertSame('456', $resource->getActualId());
    }

    public function testGetActualIdReturnsStringIdForUuid(): void
    {
        $uuid = 'abc-def-123-456';
        $resource = new PrismResource(
            PrismName::fromString('test_prism'),
            Scope::fromString('dev_alice'),
            TableName::fromString('users'),
            $uuid,
            'uuid',
            null,
            new \DateTimeImmutable()
        );

        $this->assertSame($uuid, $resource->getActualId());
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(PrismResource::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testPropertiesArePublicAndReadonly(): void
    {
        $reflection = new \ReflectionClass(PrismResource::class);

        $properties = ['prismName', 'scope', 'tableName', 'rowId', 'idColumnName', 'createdAt'];

        foreach ($properties as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue($property->isPublic(), "Property {$propertyName} should be public");
        }
    }
}
