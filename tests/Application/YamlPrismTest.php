<?php

declare(strict_types=1);

namespace Tests\Prism\Application;

use Prism\Application\Prism\YamlPrism;
use Prism\Domain\ValueObject\PrismName;
use Prism\Domain\ValueObject\Scope;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Prism\Infrastructure\FakeDatabaseNameResolver;
use Tests\Prism\Infrastructure\FakeLogger;
use Tests\Prism\Infrastructure\FakePrismDataRepository;
use Tests\Prism\Infrastructure\FakePrismLoader;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;
use Tests\Prism\Infrastructure\FakeFakeDataGenerator;

/**
 * Tests unitaires : YamlPrism
 */
final class YamlPrismTest extends TestCase
{
    private FakePrismLoader $loader;
    private FakePrismDataRepository $repository;
    private FakePrismResourceTracker $tracker;
    private FakeLogger $logger;
    private FakeFakeDataGenerator $fakeGenerator;
    private FakeDatabaseNameResolver $dbNameResolver;

    protected function setUp(): void
    {
        $this->loader = new FakePrismLoader();
        $this->repository = new FakePrismDataRepository();
        $this->tracker = new FakePrismResourceTracker();
        $this->fakeGenerator = new FakeFakeDataGenerator();
        $this->logger = new FakeLogger();
        $this->dbNameResolver = new FakeDatabaseNameResolver();
    }

    public function testConstructorShouldThrowExceptionWhenYamlNotFound(): void
    {
        // Arrange
        $prismName = PrismName::fromString('non_existent');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('YAML prism file not found');

        new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );
    }

    public function testLoadShouldExecuteSimpleInstructions(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin', 'email' => 'admin@test.com']
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getInsertedData();
        $this->assertCount(1, $insertedData);
        $this->assertSame('users', $insertedData[0]['table']);
        $this->assertSame('admin', $insertedData[0]['data']['username']);
    }

    public function testLoadShouldInitializeVariables(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => [
                'admin' => 'admin_{{ scope }}'
            ],
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => '{{ $admin }}']
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $this->assertGreaterThan(0, $this->loader->getReplacePlaceholdersCallCount());
    }

    public function testLoadShouldTrackInsertedResources(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin']
                ],
                [
                    'table' => 'users',
                    'data' => ['username' => 'user']
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $this->assertSame(2, $this->tracker->getTrackCallCount());
    }

    public function testLoadShouldResolveLookupReferences(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        // Préparer le repository pour retourner un résultat de lookup
        $this->repository->setQueryResults([
            ['id' => 42]
        ]);

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin']
                ],
                [
                    'table' => 'messages',
                    'data' => [
                        'user_id' => [
                            'table' => 'users',
                            'where' => ['username' => 'admin'],
                            'return' => 'id'
                        ],
                        'content' => 'Hello'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getInsertedData();
        $this->assertCount(2, $insertedData);
        $this->assertSame('messages', $insertedData[1]['table']);
        $this->assertSame(42, $insertedData[1]['data']['user_id']);
    }

    public function testLoadShouldHandlePivotTracking(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->repository->setQueryResults([
            ['id' => 99]
        ]);

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users_acl',
                    'data' => ['user_id' => 99, 'acl_id' => 1],
                    'pivot' => [
                        'id' => 99,
                        'column' => 'user_id'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $resources = $this->tracker->getAllResources();
        $this->assertCount(1, $resources);
        $this->assertSame('user_id', $resources[0]->idColumnName);
        $this->assertSame('99', $resources[0]->rowId);
    }

    public function testLoadShouldThrowExceptionWhenTableMissing(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'data' => ['username' => 'admin']
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing "table" key');

        $prism->load($scope);
    }

    public function testLoadShouldThrowExceptionWhenDataMissing(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users'
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing "data" key');

        $prism->load($scope);
    }

    public function testPurgeShouldUseParentPurgeByDefault(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin']
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act
        $prism->purge($scope);

        // Assert
        $this->assertSame(1, $this->tracker->getPurgeCallCount());
    }

    public function testPurgeShouldExecuteCustomPurgeInstructions(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin']
                ]
            ],
            'purge' => [
                [
                    'table' => 'users',
                    'where' => ['username' => 'admin']
                ],
                [
                    'purge_pivot' => true
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act
        $prism->purge($scope);

        // Assert - Vérifie que le purge personnalisé a été exécuté
        $this->assertTrue($this->logger->hasLog('Purge personnalisé', 'debug'));
    }

    public function testGetNameShouldReturnPrismName(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');

        $this->loader->setPrism('test_prism', [
            'load' => []
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $name = $prism->getName();

        // Assert
        $this->assertTrue($name->equals($prismName));
    }

    public function testLoadShouldConvertTypesWhenSpecified(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'events',
                    'data' => [
                        'name' => 'Event1',
                        'created_at' => '2025-01-01 10:00:00'
                    ],
                    'types' => [
                        'created_at' => 'datetime_immutable'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $this->assertSame(1, $this->loader->getConvertTypeCallCount());
    }

    public function testLoadShouldResetVariablesAfterExecution(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => ['admin' => 'admin_{{ scope }}'],
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $this->assertCount(0, $this->loader->getVariables());
    }

    public function testLoadShouldThrowExceptionWhenLookupFails(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        // Repository retourne résultat vide
        $this->repository->setQueryResults([]);

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'messages',
                    'data' => [
                        'user_id' => [
                            'table' => 'users',
                            'where' => ['username' => 'nonexistent'],
                            'return' => 'id'
                        ],
                        'content' => 'Hello'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Lookup failed');

        $prism->load($scope);
    }

    public function testLoadShouldHandlePivotWithLookup(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->repository->setQueryResults([
            ['id' => 123]
        ]);

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users_acl',
                    'data' => ['user_id' => 123, 'acl_id' => 1],
                    'pivot' => [
                        'id' => [
                            'table' => 'users',
                            'where' => ['username' => 'admin'],
                            'return' => 'id'
                        ],
                        'column' => 'user_id'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $resources = $this->tracker->getAllResources();
        $this->assertSame('123', $resources[0]->rowId);
    }

    public function testPurgeShouldSkipInstructionWithoutWhereClause(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ],
            'purge' => [
                [
                    'table' => 'users'
                    // WHERE manquant
                ],
                ['purge_pivot' => true]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act
        $prism->purge($scope);

        // Assert
        $this->assertTrue($this->logger->hasLog('Purge instruction sans WHERE clause ignorée', 'warning'));
    }

    public function testPurgeShouldHandleLookupFailureGracefully(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ],
            'purge' => [
                [
                    'table' => 'messages',
                    'where' => [
                        'user_id' => [
                            'table' => 'users',
                            'where' => ['username' => 'nonexistent'],
                            'return' => 'id'
                        ]
                    ]
                ],
                ['purge_pivot' => true]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Forcer lookup à échouer
        $this->repository->setQueryResults([]);

        // Act - Ne devrait pas lancer d'exception
        $prism->purge($scope);

        // Assert
        $this->assertTrue($this->logger->hasLog('Lookup échoué dans purge', 'warning'));
    }

    public function testLoadShouldThrowExceptionForInvalidPivotId(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users_acl',
                    'data' => ['user_id' => 123],
                    'pivot' => [
                        'id' => [], // Invalid: tableau vide
                        'column' => 'user_id'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pivot id');

        $prism->load($scope);
    }

    public function testLoadShouldThrowExceptionWhenPivotIdMissing(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users_acl',
                    'data' => ['user_id' => 123],
                    'pivot' => [
                        'column' => 'user_id'
                        // 'id' manquant
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing "id" key in pivot');

        $prism->load($scope);
    }

    public function testPurgeShouldInitializeVariables(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => ['admin' => 'admin_{{ scope }}'],
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act
        $prism->purge($scope);

        // Assert - Les variables devraient être réinitialisées après purge
        $this->assertCount(0, $this->loader->getVariables());
    }

    public function testLoadShouldLogAllSteps(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => ['admin' => 'admin'],
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $this->assertTrue($this->logger->hasLog('Chargement du scénario YAML', 'info'));
        $this->assertTrue($this->logger->hasLog('Variables initialisées', 'debug'));
        $this->assertTrue($this->logger->hasLog('Instruction #', 'info'));
        $this->assertTrue($this->logger->hasLog('Scénario YAML chargé', 'info'));
    }

    public function testPurgeWithPurgePivotInMiddle(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ],
            'purge' => [
                ['table' => 'logs', 'where' => ['user_id' => 1]],
                ['purge_pivot' => true],  // Purge pivot au milieu
                ['table' => 'cache', 'where' => ['key' => 'test']]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act
        $prism->purge($scope);

        // Assert
        $this->assertTrue($this->logger->hasLog('Purge automatique (pivot) déclenché', 'info'));
    }

    public function testLoadShouldThrowExceptionForInvalidLookupResultType(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        // Repository retourne un type invalide (array au lieu de int/string)
        $this->repository->setQueryResultsWithInvalidType();

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'messages',
                    'data' => [
                        'user_id' => [
                            'table' => 'users',
                            'where' => ['username' => 'admin'],
                            'return' => 'id'
                        ],
                        'content' => 'Hello'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid lookup result type');

        $prism->load($scope);
    }

    public function testPurgeShouldThrowExceptionForMissingTableInPurgeInstruction(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']]
            ],
            'purge' => [
                [
                    'where' => ['username' => 'admin']
                    // 'table' manquant
                ],
                ['purge_pivot' => true]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing "table" key in purge instruction');

        $prism->purge($scope);
    }

    public function testLoadShouldThrowExceptionForInvalidPivotIdType(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin'],
                    'pivot' => [
                        'id' => 3.14, // Invalid type: float (not int, string ou lookup)
                        'column' => 'custom_id'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pivot id: must be a lookup reference or a direct value');

        $prism->load($scope);
    }

    public function testLoadShouldThrowExceptionForMissingPivotId(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => ['username' => 'admin'],
                    'pivot' => [
                        // 'id' manquant
                        'column' => 'custom_id'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing "id" key in pivot configuration');

        $prism->load($scope);
    }

    public function testPurgeShouldResolveLookupInWhereClause(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        // Simuler que l'utilisateur existe déjà dans la base
        $this->repository->insert('users', ['username' => 'admin'], []);

        // Définir le résultat du lookup (l'user_id=1 quand on cherche username=admin)
        $this->repository->setQueryResults([
            ['id' => 1]
        ]);

        $this->loader->setPrism('test_prism', [
            'load' => [
                ['table' => 'users', 'data' => ['username' => 'admin']],
                ['table' => 'messages', 'data' => ['user_id' => 1, 'content' => 'Hello']]
            ],
            'purge' => [
                [
                    'table' => 'messages',
                    'where' => [
                        'user_id' => [
                            'table' => 'users',
                            'where' => ['username' => 'admin'],
                            'return' => 'id'
                        ]
                    ]
                ],
                ['purge_pivot' => true]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        $prism->load($scope);

        // Act
        $prism->purge($scope);

        // Assert - Vérifie que le lookup a été résolu dans le purge
        $this->assertTrue($this->logger->hasLog('Lookup résolu dans purge', 'debug'));
    }

    public function testLoadShouldHandleHashWithScope(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => 'admin',
                        'password' => '{{ hash(scope) }}'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getInsertedData();
        $this->assertCount(1, $insertedData);
        $password = $insertedData[0]['data']['password'];

        // Vérifier que c'est bien un hash bcrypt
        $this->assertStringStartsWith('$2y$', $password);

        // Vérifier que le hash correspond bien au scope
        $this->assertTrue(password_verify('test_scope', $password));
    }

    public function testLoadShouldHandleHashWithGlobalVariable(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => [
                'password_plain' => 'my_secret'
            ],
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => 'admin',
                        'password' => '{{ hash($password_plain) }}'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getInsertedData();
        $this->assertCount(1, $insertedData);
        $password = $insertedData[0]['data']['password'];

        // Vérifier que c'est bien un hash bcrypt
        $this->assertStringStartsWith('$2y$', $password);

        // Vérifier que le hash correspond bien à la variable
        $this->assertTrue(password_verify('my_secret', $password));
    }

    public function testLoadShouldHandleHashWithTemporaryVariable(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => 'admin_user',
                        'password' => '{{ hash($username) }}'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getInsertedData();
        $this->assertCount(1, $insertedData);
        $password = $insertedData[0]['data']['password'];

        // Vérifier que c'est bien un hash bcrypt
        $this->assertStringStartsWith('$2y$', $password);

        // Vérifier que le hash correspond bien à la variable temporaire (username)
        $this->assertTrue(password_verify('admin_user', $password));
    }

    public function testLoadShouldHandleHashWithLiteralString(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => 'admin',
                        'password' => '{{ hash("fixed_password") }}'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getInsertedData();
        $this->assertCount(1, $insertedData);
        $password = $insertedData[0]['data']['password'];

        // Vérifier que c'est bien un hash bcrypt
        $this->assertStringStartsWith('$2y$', $password);

        // Vérifier que le hash correspond bien à la chaîne littérale
        $this->assertTrue(password_verify('fixed_password', $password));
    }

    public function testLoadShouldThrowExceptionForInvalidHashVariable(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => 'admin',
                        'password' => '{{ hash($undefined_var) }}'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Undefined variable in hash(): $undefined_var');

        $prism->load($scope);
    }

    public function testLoadShouldHandleTruncatePipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope_with_long_name');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ scope|truncate(8) }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('test_sco', $insertedData['username']); // Tronqué à 8 caractères
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandleTrimPipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => [
                'text' => '  Hello World  '
            ],
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ $text|trim }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('Hello World', $insertedData['username']);
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandleUppercasePipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ scope|uppercase }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('TEST_SCOPE', $insertedData['username']);
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandleLowercasePipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => [
                'UPPERCASE_VAR' => 'HELLO WORLD'
            ],
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ UPPERCASE_VAR|lowercase }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('hello world', $insertedData['username']);
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandleCapitalizePipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => [
                'text' => 'hello world'
            ],
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ $text|capitalize }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('Hello world', $insertedData['username']);
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandleReplacePipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ scope|replace("_", "-") }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('test-scope', $insertedData['username']);
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandleMultiplePipesChained(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'vars' => [
                'text' => '  Hello World  '
            ],
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ $text|trim|lowercase|capitalize }}',
                        'code' => '{{ scope|uppercase|replace("_", "-")|truncate(8) }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('Hello world', $insertedData['username']); // trim + lowercase + capitalize
        $this->assertSame('TEST-SCO', $insertedData['code']); // uppercase + replace + truncate
        $this->assertSame('user@example.com', $insertedData['email']);
    }

    public function testLoadShouldHandlePipesWithHashFunction(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => 'admin',
                        'password' => '{{ hash(scope)|truncate(24) }}'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $insertedData = $this->repository->getLastInsertedData();
        $this->assertSame('admin', $insertedData['username']);
        // Le hash bcrypt fait 60 caractères, tronqué à 24
        $this->assertSame(24, strlen($insertedData['password']));
        $this->assertStringStartsWith('$2y$', $insertedData['password']);
    }

    public function testLoadShouldThrowExceptionForUnknownPipe(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ scope|unknown_pipe }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown pipe: unknown_pipe');

        $prism->load($scope);
    }

    public function testLoadShouldThrowExceptionForTruncateWithoutArgument(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ scope|truncate() }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pipe truncate() requires 1 argument');

        $prism->load($scope);
    }

    public function testLoadShouldThrowExceptionForReplaceWithoutTwoArguments(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users',
                    'data' => [
                        'username' => '{{ scope|replace("_") }}',
                        'email' => 'user@example.com'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pipe replace() requires 2 arguments');

        $prism->load($scope);
    }

    public function testLoadShouldHandlePivotWithStringPlaceholder(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        // Le repository retourne l'ID pour le lookup
        $this->repository->setQueryResults([
            ['id' => 456]
        ]);

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users_acl',
                    'data' => [
                        'user_id' => [
                            'table' => 'users',
                            'where' => ['username' => 'alice'],
                            'return' => 'id'
                        ],
                        'acl_id' => 1
                    ],
                    'pivot' => [
                        'id' => '{{ $user_id }}',
                        'column' => 'user_id'
                    ]
                ]
            ]
        ]);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act
        $prism->load($scope);

        // Assert
        $resources = $this->tracker->getAllResources();
        $this->assertCount(1, $resources);
        $this->assertSame('user_id', $resources[0]->idColumnName);
        $this->assertSame('456', $resources[0]->rowId);
    }

    public function testLoadShouldThrowExceptionWhenPivotIdTypeIsInvalid(): void
    {
        // Arrange
        $prismName = PrismName::fromString('test_prism');
        $scope = Scope::fromString('test_scope');

        $this->loader->setPrism('test_prism', [
            'load' => [
                [
                    'table' => 'users_acl',
                    'data' => [
                        'user_id' => 789,
                        'acl_id' => 1
                    ],
                    'pivot' => [
                        'id' => '{{ $invalid }}',
                        'column' => 'user_id'
                    ]
                ]
            ]
        ]);

        // Simuler un type invalide après résolution
        $this->loader->setInvalidPlaceholderResolution('_pivot_id', ['invalid' => 'array']);

        $prism = new YamlPrism(
            $prismName,
            $this->loader,
            $this->repository,
            $this->tracker,
            $this->fakeGenerator,
            $this->dbNameResolver,
            $this->logger,
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pivot id type after placeholder resolution');

        $prism->load($scope);
    }
}
