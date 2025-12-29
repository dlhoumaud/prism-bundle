<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\Prism\AbstractPrism;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakeDatabaseNameResolver;
use Tests\Prism\Infrastructure\FakeLogger;
use Tests\Prism\Infrastructure\FakePrismDataRepository;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;
use Tests\Prism\Infrastructure\FakeFakeDataGenerator;

/**
 * Tests unitaires : AbstractPrism
 */
final class AbstractPrismTest extends TestCase
{
    private FakePrismDataRepository $repository;
    private FakePrismResourceTracker $tracker;
    private FakeLogger $logger;
    private FakeFakeDataGenerator $fakeGenerator;
    private FakeDatabaseNameResolver $dbNameResolver;
    private ConcreteTestPrism $prism;

    protected function setUp(): void
    {
        $this->repository = new FakePrismDataRepository();
        $this->tracker = new FakePrismResourceTracker();
        $this->logger = new FakeLogger();
        $this->fakeGenerator = new FakeFakeDataGenerator();
        $this->dbNameResolver = new FakeDatabaseNameResolver();

        $this->prism = new ConcreteTestPrism(
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger
        );
    }

    public function testInsertAndTrackShouldInsertAndTrackResource(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $data = ['name' => 'John', 'email' => 'john@example.com'];

        // Act
        $this->prism->publicLoad($scope);
        $id = $this->prism->publicInsertAndTrack('users', $data);

        // Assert
        $this->assertSame(1, $id, 'L\'ID devrait être 1');
        $this->assertSame(1, $this->tracker->getTrackCallCount(), 'Le tracking devrait être appelé une fois');

        $resources = $this->tracker->findByPrismAndScope(
            PrismName::fromString('concrete_test_prism'),
            $scope
        );
        $this->assertCount(1, $resources);
        $this->assertSame('users', $resources[0]->tableName->toString());
    }

    public function testInsertAndTrackWithCustomIdColumn(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $data = ['user_id' => 'uuid-123', 'name' => 'Jane'];

        // Act
        $this->prism->publicLoad($scope);
        $id = $this->prism->publicInsertAndTrack('users_acl', $data, [], 'user_id');

        // Assert
        $resources = $this->tracker->findByPrismAndScope(
            PrismName::fromString('concrete_test_prism'),
            $scope
        );
        $this->assertCount(1, $resources);
        $this->assertSame('user_id', $resources[0]->idColumnName);
    }

    public function testTrackResourceShouldTrackWithCorrectParameters(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');

        // Act
        $this->prism->publicLoad($scope);
        $this->prism->publicTrackResource('messages', 42);

        // Assert
        $this->assertSame(1, $this->tracker->getTrackCallCount());
        $resources = $this->tracker->findByPrismAndScope(
            PrismName::fromString('concrete_test_prism'),
            $scope
        );
        $this->assertCount(1, $resources);
        $this->assertSame('messages', $resources[0]->tableName->toString());
        $this->assertSame('42', $resources[0]->rowId);
    }

    public function testPurgeShouldDeleteResourcesInReverseOrder(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');

        // Charger et créer des ressources
        $this->prism->publicLoad($scope);
        $this->prism->publicInsertAndTrack('users', ['name' => 'User1']);
        $this->prism->publicInsertAndTrack('messages', ['content' => 'Message1']);
        $this->prism->publicInsertAndTrack('messages', ['content' => 'Message2']);

        // Act
        $this->prism->purge($scope);

        // Assert
        $statements = $this->repository->getExecutedStatements();
        $this->assertCount(3, $statements, '3 DELETE devraient être exécutés');

        // Vérifier l'ordre inverse
        $this->assertStringContainsString('DELETE FROM messages', $statements[0]['sql']);
        $this->assertStringContainsString('DELETE FROM messages', $statements[1]['sql']);
        $this->assertStringContainsString('DELETE FROM users', $statements[2]['sql']);
    }

    public function testPurgeShouldCleanTracker(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');

        $this->prism->publicLoad($scope);
        $this->prism->publicInsertAndTrack('users', ['name' => 'User1']);

        // Act
        $this->prism->purge($scope);

        // Assert
        $this->assertSame(1, $this->tracker->getPurgeCallCount());
        $resources = $this->tracker->findByPrismAndScope(
            PrismName::fromString('concrete_test_prism'),
            $scope
        );
        $this->assertCount(0, $resources, 'Toutes les ressources devraient être supprimées du tracker');
    }

    public function testPurgeShouldHandleDeleteErrorsGracefully(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $repositoryWithError = new FakePrismDataRepositoryWithError();
        $prism = new ConcreteTestPrism(
            $repositoryWithError,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger
        );

        $prism->publicLoad($scope);
        $prism->publicInsertAndTrack('users', ['name' => 'User1']);

        // Act - Ne devrait pas lancer d'exception
        $prism->purge($scope);

        // Assert
        $this->assertTrue($this->logger->hasLog('Impossible de supprimer', 'warning'));
    }

    public function testPurgeShouldHandleDeleteErrorsWithDbNameGracefully(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $repositoryWithError = new FakePrismDataRepositoryWithError();
        $prism = new ConcreteTestPrism(
            $repositoryWithError,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger
        );

        $prism->publicLoad($scope);
        // Insert avec dbName pour tester le chemin avec db_name dans le catch
        $prism->publicInsertAndTrackWithDbName('users', ['name' => 'User1'], [], 'id', 'test_db');

        // Act - Ne devrait pas lancer d'exception
        $prism->purge($scope);

        // Assert - Vérifie que le warning est bien loggé
        $this->assertTrue($this->logger->hasLog('Impossible de supprimer', 'warning'));
    }

    public function testGetScopeShouldThrowExceptionWhenNotSet(): void
    {
        // Arrange - Scénario créé mais load() jamais appelé

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le scope n\'a pas été défini');

        $this->prism->publicGetScope();
    }

    public function testGetScopeShouldReturnCurrentScopeAfterLoad(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');

        // Act
        $this->prism->publicLoad($scope);
        $returnedScope = $this->prism->publicGetScope();

        // Assert
        $this->assertTrue($scope->equals($returnedScope));
    }

    public function testGetRepositoryShouldReturnInjectedRepository(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $this->prism->publicLoad($scope);

        // Act
        $repository = $this->prism->publicGetRepository();

        // Assert
        $this->assertSame($this->repository, $repository);
    }

    public function testConstructorWithoutLoggerShouldUseNullLogger(): void
    {
        // Arrange & Act - Créer un scénario sans logger
        $prismWithoutLogger = new ConcreteTestPrism(
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            null // Logger explicitement null
        );

        $scope = Scope::fromString('test_scope');
        $prismWithoutLogger->publicLoad($scope);
        $prismWithoutLogger->publicTrackResource('users', 123);

        // Assert - Aucune exception ne devrait être lancée
        // Le NullLogger absorbe tous les logs sans erreur
        $this->assertSame(1, $this->tracker->getTrackCallCount());
    }

    public function testLoggerShouldLogTrackOperations(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');

        // Act
        $this->prism->publicLoad($scope);
        $this->prism->publicTrackResource('users', 123);

        // Assert
        $this->assertTrue($this->logger->hasLog('Ressource tracée', 'debug'));
    }

    public function testLoggerShouldLogPurgeOperations(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');

        $this->prism->publicLoad($scope);
        $this->prism->publicInsertAndTrack('users', ['name' => 'User1']);

        // Act
        $this->prism->purge($scope);

        // Assert
        $this->assertTrue($this->logger->hasLog('Purge de', 'info'));
        $this->assertTrue($this->logger->hasLog('Ressource supprimée', 'debug'));
    }

    public function testInsertAndTrackWithNullIdShouldNotTrack(): void
    {
        // Arrange
        $scope = Scope::fromString('test_scope');
        $this->repository->setQueryResults([]); // Pas d'ID retourné

        // Simuler un insert sans ID (table sans auto-increment)
        $prismNoId = new ConcreteTestPrismNoId(
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger
        );

        // Act
        $prismNoId->publicLoad($scope);
        $id = $prismNoId->publicInsertAndTrackWithNullId('logs', ['message' => 'test']);

        // Assert
        $this->assertNull($id);
        $this->assertSame(0, $this->tracker->getTrackCallCount(), 'Aucun tracking ne devrait avoir lieu si ID null');
    }

    public function testMultipleScopesShouldBeIsolated(): void
    {
        // Arrange
        $scope1 = Scope::fromString('scope_1');
        $scope2 = Scope::fromString('scope_2');

        // Act
        $this->prism->publicLoad($scope1);
        $this->prism->publicInsertAndTrack('users', ['name' => 'User1']);

        $this->prism->publicLoad($scope2);
        $this->prism->publicInsertAndTrack('users', ['name' => 'User2']);

        // Assert
        $resources1 = $this->tracker->findByPrismAndScope(
            PrismName::fromString('concrete_test_prism'),
            $scope1
        );
        $resources2 = $this->tracker->findByPrismAndScope(
            PrismName::fromString('concrete_test_prism'),
            $scope2
        );

        $this->assertCount(1, $resources1);
        $this->assertCount(1, $resources2);
    }

    public function testFakeMethodGeneratesFakeData(): void
    {
        // Arrange
        $scope = Scope::fromString('test');
        $this->prism->publicLoad($scope);

        // Act
        $username = $this->prism->publicFake('user');
        $email = $this->prism->publicFake('email');
        $id = $this->prism->publicFake('id');

        // Assert
        $this->assertIsString($username);
        $this->assertStringContainsString('_test', $username);
        $this->assertIsString($email);
        $this->assertStringContainsString('_test', $email);
        $this->assertIsInt($id);
    }

    public function testFakeMethodPassesScopeToGenerator(): void
    {
        // Arrange
        $scope = Scope::fromString('production');
        $this->prism->publicLoad($scope);

        // Act
        $result = $this->prism->publicFake('user');

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('_production', $result);
    }

    public function testFakeMethodPassesParametersToGenerator(): void
    {
        // Arrange
        $this->fakeGenerator->setFixedValue('text', 'custom_text');
        $scope = Scope::fromString('test');
        $this->prism->publicLoad($scope);

        // Act
        $result = $this->prism->publicFake('text');

        // Assert
        $this->assertEquals('custom_text', $result);
    }
}

/**
 * Classe concrète pour tester AbstractPrism
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
final class ConcreteTestPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('concrete_test_prism');
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
    }

    // Méthodes publiques pour tester les protected
    public function publicInsertAndTrack(
        string $tableName,
        array $data,
        array $types = [],
        string $idColumnName = 'id'
    ): int|string|null {
        // phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
        return $this->insertAndTrack($tableName, $data, $types, $idColumnName);
    }

    public function publicTrackResource(string $tableName, string|int $rowId, string $idColumnName = 'id'): void
    {
        $this->trackResource($tableName, $rowId, $idColumnName);
    }

    public function publicGetScope(): Scope
    {
        return $this->getScope();
    }

    public function publicLoad(Scope $scope): void
    {
        $this->load($scope);
    }

    public function publicGetRepository(): FakePrismDataRepository
    {
        /** @var FakePrismDataRepository */
        return $this->getRepository();
    }

    public function publicFake(string $type, mixed ...$params): string|int|float|bool
    {
        return $this->fake($type, ...$params);
    }

    public function publicInsertAndTrackWithDbName(
        string $tableName,
        array $data,
        array $types = [],
        string $idColumnName = 'id',
        ?string $dbName = null
    ): int|string|null {
        return $this->insertAndTrack($tableName, $data, $types, $idColumnName, $dbName);
    }
}

/**
 * Classe concrète qui retourne null pour l'ID
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
final class ConcreteTestPrismNoId extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('concrete_test_prism_no_id');
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
    }

    public function publicLoad(Scope $scope): void
    {
        $this->load($scope);
    }

    public function publicInsertAndTrackWithNullId(string $tableName, array $data): int|string|null
    {
        // Override pour simuler un retour null
        $this->repository->insert($tableName, $data);
        return null; // Simulation d'une table sans ID
    }
}

/**
 * FakeRepository qui simule des erreurs lors des DELETE
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
final class FakePrismDataRepositoryWithError extends FakePrismDataRepository
{
    public function executeStatement(string $sql, array $params = []): int
    {
        if (str_contains($sql, 'DELETE')) {
            throw new \RuntimeException('Simulated DELETE error');
        }
        return parent::executeStatement($sql, $params);
    }
}
