<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\UseCase\LoadPrism;
use Prism\Domain\Exception\PrismLoadException;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakeDatabaseConnection;
use Tests\Prism\Infrastructure\FakeLogger;
use Tests\Prism\Infrastructure\FakePrism;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;

/**
 * Tests unitaires : LoadPrism Use Case
 */
final class LoadPrismTest extends TestCase
{
    private FakeDatabaseConnection $connection;
    private FakePrismResourceTracker $tracker;
    private FakeLogger $logger;
    private LoadPrism $useCase;

    protected function setUp(): void
    {
        $this->connection = new FakeDatabaseConnection();
        $this->tracker = new FakePrismResourceTracker();
        $this->logger = new FakeLogger();

        $this->useCase = new LoadPrism(
            $this->connection,
            $this->tracker,
            $this->logger
        );
    }

    public function testExecuteShouldLoadPrismSuccessfully(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertSame(1, $prism->getLoadCallCount(), 'Le scénario devrait être chargé une fois');
        $this->assertSame(
            1,
            $prism->getPurgeCallCount(),
            'Le scénario devrait être purgé une fois (purge préalable)'
        );
        $this->assertCount(1, $prism->getLoadedScopes(), 'Un scope devrait être chargé');
        $this->assertTrue($prism->getLoadedScopes()[0]->equals($scope), 'Le scope chargé devrait correspondre');
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

    public function testExecuteShouldPurgeBeforeLoading(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert - Vérification de l'ordre d'appel
        $this->assertSame(1, $prism->getPurgeCallCount(), 'Purge devrait être appelé');
        $this->assertSame(1, $prism->getLoadCallCount(), 'Load devrait être appelé');
        $this->assertCount(1, $prism->getPurgedScopes(), 'Un scope devrait être purgé');
        $this->assertTrue($prism->getPurgedScopes()[0]->equals($scope), 'Le scope purgé devrait correspondre');
    }

    public function testExecuteShouldLogLoadingSteps(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);

        // Act
        $this->useCase->execute($prism, $scope);

        // Assert
        $this->assertTrue(
            $this->logger->hasLog('Chargement du scénario', 'info'),
            'Un log info de début devrait exister'
        );
        $this->assertTrue(
            $this->logger->hasLog('Purge du scope', 'debug'),
            'Un log debug de purge devrait exister'
        );
        $this->assertTrue(
            $this->logger->hasLog('Chargement des données', 'debug'),
            'Un log debug de chargement devrait exister'
        );
        $this->assertTrue(
            $this->logger->hasLog('chargé avec succès', 'info'),
            'Un log info de succès devrait exister'
        );
    }

    public function testExecuteShouldRollbackOnLoadFailure(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);
        $prism->simulateLoadFailure();

        // Act & Assert
        try {
            $this->useCase->execute($prism, $scope);
            $this->fail('Une exception devrait être lancée');
        } catch (PrismLoadException $e) {
            // Assert
            $this->assertSame(1, $this->connection->getTransactionCount(), 'Une transaction devrait être démarrée');
            $this->assertSame(0, $this->connection->getCommitCount(), 'Aucun commit ne devrait avoir lieu');
            $this->assertSame(1, $this->connection->getRollbackCount(), 'Un rollback devrait avoir lieu');
            $this->assertFalse($this->connection->isInTransaction(), 'La transaction devrait être terminée');
        }
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
        } catch (PrismLoadException $e) {
            // Assert
            $this->assertSame(1, $this->connection->getRollbackCount(), 'Un rollback devrait avoir lieu');
            $this->assertSame(0, $this->connection->getCommitCount(), 'Aucun commit ne devrait avoir lieu');
        }
    }

    public function testExecuteShouldLogErrorOnFailure(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);
        $prism->simulateLoadFailure();

        // Act & Assert
        try {
            $this->useCase->execute($prism, $scope);
        } catch (PrismLoadException $e) {
            // Assert
            $this->assertTrue(
                $this->logger->hasLog('Échec du chargement', 'error'),
                'Un log error devrait exister'
            );
        }
    }

    public function testExecuteShouldThrowPrismLoadExceptionOnFailure(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');
        $prism = new FakePrism($prismName);
        $prism->simulateLoadFailure();

        // Act & Assert
        $this->expectException(PrismLoadException::class);
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
        $this->assertSame(2, $prism->getLoadCallCount(), 'Le scénario devrait être chargé deux fois');
        $this->assertSame(2, $prism->getPurgeCallCount(), 'Le scénario devrait être purgé deux fois');
        $this->assertCount(2, $prism->getLoadedScopes(), 'Deux scopes devraient être chargés');
        $this->assertTrue($prism->getLoadedScopes()[0]->equals($scope1));
        $this->assertTrue($prism->getLoadedScopes()[1]->equals($scope2));
    }
}
