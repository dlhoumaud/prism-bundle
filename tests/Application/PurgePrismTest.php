<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\UseCase\PurgePrism;
use Prism\Domain\Exception\ScopePurgeFailedException;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakeDatabaseConnection;
use Tests\Prism\Infrastructure\FakeLogger;
use Tests\Prism\Infrastructure\FakePrism;

/**
 * Tests unitaires : PurgePrism Use Case
 */
final class PurgePrismTest extends TestCase
{
    private FakeDatabaseConnection $connection;
    private FakeLogger $logger;
    private PurgePrism $useCase;

    protected function setUp(): void
    {
        $this->connection = new FakeDatabaseConnection();
        $this->logger = new FakeLogger();

        $this->useCase = new PurgePrism(
            $this->connection,
            $this->logger
        );
    }

    public function testExecuteShouldPurgePrismSuccessfully(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertSame(1, $prism->getPurgeCallCount(), 'Le scénario devrait être purgé une fois');
        $this->assertCount(1, $prism->getPurgedScopes(), 'Un scope devrait être purgé');
        $this->assertTrue($prism->getPurgedScopes()[0]->equals($scope), 'Le scope purgé devrait correspondre');
    }

    public function testExecuteShouldUseTransaction(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertSame(1, $this->connection->getTransactionCount(), 'Une transaction devrait être démarrée');
        $this->assertSame(1, $this->connection->getCommitCount(), 'La transaction devrait être commitée');
        $this->assertSame(0, $this->connection->getRollbackCount(), 'Aucun rollback ne devrait avoir lieu');
        $this->assertFalse($this->connection->isInTransaction(), 'La transaction devrait être terminée');
    }

    public function testExecuteShouldLogPurgeSteps(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertTrue(
            $this->logger->hasLog('Purge du scénario', 'info'),
            'Un log info de début devrait exister'
        );
        $this->assertTrue(
            $this->logger->hasLog('purgé avec succès', 'info'),
            'Un log info de succès devrait exister'
        );
    }

    public function testExecuteShouldRollbackOnPurgeFailure(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);
        $prism->simulatePurgeFailure();

        // Act & Assert
        try {
            $this->useCase->execute($prism, $scope);
            $this->fail('Une exception devrait être lancée');
        } catch (ScopePurgeFailedException $e) {
            // Assert
            $this->assertSame(1, $this->connection->getTransactionCount(), 'Une transaction devrait être démarrée');
            $this->assertSame(0, $this->connection->getCommitCount(), 'Aucun commit ne devrait avoir lieu');
            $this->assertSame(1, $this->connection->getRollbackCount(), 'Un rollback devrait avoir lieu');
            $this->assertFalse($this->connection->isInTransaction(), 'La transaction devrait être terminée');
        }
    }

    public function testExecuteShouldLogErrorOnFailure(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);
        $prism->simulatePurgeFailure();

        // Act & Assert
        try {
            $this->useCase->execute($prism, $scope);
        } catch (ScopePurgeFailedException $e) {
            // Assert
            $this->assertTrue(
                $this->logger->hasLog('Échec de la purge', 'error'),
                'Un log error devrait exister'
            );
        }
    }

    public function testExecuteShouldThrowScopePurgeFailedExceptionOnFailure(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);
        $prism->simulatePurgeFailure();

        // Act & Assert
        $this->expectException(ScopePurgeFailedException::class);
        $this->useCase->execute($prism, $scope);
    }

    public function testExecuteShouldHandleMultipleScopes(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope1 = Scope::fromString('scope_1');
        $scope2 = Scope::fromString('scope_2');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope1);
        $this->useCase->execute($prism, $scope2);

        // Assert
        $this->assertSame(2, $prism->getPurgeCallCount(), 'Le scénario devrait être purgé deux fois');
        $this->assertCount(2, $prism->getPurgedScopes(), 'Deux scopes devraient être purgés');
        $this->assertTrue($prism->getPurgedScopes()[0]->equals($scope1));
        $this->assertTrue($prism->getPurgedScopes()[1]->equals($scope2));
    }

    public function testExecuteShouldNotCallLoadMethod(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertSame(0, $prism->getLoadCallCount(), 'Load ne devrait jamais être appelé lors d\'une purge');
    }

    public function testExecuteShouldPurgeSameScopeMultipleTimes(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);
        $this->useCase->execute($prism, $scope);
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertSame(3, $prism->getPurgeCallCount(), 'Le scénario devrait être purgé 3 fois');
        $this->assertSame(3, $this->connection->getCommitCount(), '3 commits devraient avoir lieu');
    }
}
