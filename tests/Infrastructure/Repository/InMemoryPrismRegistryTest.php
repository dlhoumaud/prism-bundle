<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure\Repository;

use Prism\Domain\ValueObject\PrismName;
use Prism\Infrastructure\Repository\InMemoryPrismRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Prism\Infrastructure\FakePrism;
use Tests\Prism\Infrastructure\FakePrismDataRepository;
use Tests\Prism\Infrastructure\FakePrismLoader;
use Tests\Prism\Infrastructure\FakePrismResourceTracker;
use Tests\Prism\Infrastructure\FakeLogger;
use Tests\Prism\Infrastructure\FakeFakeDataGenerator;
use Tests\Prism\Infrastructure\FakeDatabaseNameResolver;

/**
 * Tests unitaires pour InMemoryPrismRegistry
 */
final class InMemoryPrismRegistryTest extends TestCase
{
    public function testConstructorRegistersProvidedPrisms(): void
    {
        $prism1 = new FakePrism(PrismName::fromString('prism_1'));
        $prism2 = new FakePrism(PrismName::fromString('prism_2'));

        $registry = $this->createRegistry([$prism1, $prism2]);

        $this->assertTrue($registry->has(PrismName::fromString('prism_1')));
        $this->assertTrue($registry->has(PrismName::fromString('prism_2')));
    }

    public function testGetReturnsRegisteredPrism(): void
    {
        $prism = new FakePrism(PrismName::fromString('test_prism'));
        $registry = $this->createRegistry([$prism]);

        $result = $registry->get(PrismName::fromString('test_prism'));

        $this->assertSame($prism, $result);
    }

    public function testGetReturnsNullForUnknownPrism(): void
    {
        $registry = $this->createRegistry([]);

        $result = $registry->get(PrismName::fromString('unknown'));

        $this->assertNull($result);
    }

    public function testGetFallsBackToYamlPrism(): void
    {
        $yamlLoader = new FakePrismLoader();
        $yamlLoader->setPrism('yaml_prism', ['load' => []]);

        $registry = $this->createRegistry([], $yamlLoader);

        $result = $registry->get(PrismName::fromString('yaml_prism'));

        $this->assertNotNull($result);
        $this->assertSame('yaml_prism', $result->getName()->toString());
    }

    public function testHasReturnsTrueForRegisteredPrism(): void
    {
        $prism = new FakePrism(PrismName::fromString('existing'));
        $registry = $this->createRegistry([$prism]);

        $this->assertTrue($registry->has(PrismName::fromString('existing')));
    }

    public function testHasReturnsFalseForUnknownPrism(): void
    {
        $registry = $this->createRegistry([]);

        $this->assertFalse($registry->has(PrismName::fromString('missing')));
    }

    public function testAllReturnsAllRegisteredPrisms(): void
    {
        $prism1 = new FakePrism(PrismName::fromString('prism_1'));
        $prism2 = new FakePrism(PrismName::fromString('prism_2'));

        $registry = $this->createRegistry([$prism1, $prism2]);

        $all = $registry->all();

        $this->assertCount(2, $all);
        $this->assertContains($prism1, $all);
        $this->assertContains($prism2, $all);
    }

    public function testAllReturnsEmptyArrayWhenNoPrisms(): void
    {
        $registry = $this->createRegistry([]);

        $all = $registry->all();

        $this->assertIsArray($all);
        $this->assertEmpty($all);
    }

    public function testPhpPrismHasPriorityOverYaml(): void
    {
        $phpPrism = new FakePrism(PrismName::fromString('test_prism'));

        $yamlLoader = new FakePrismLoader();
        $yamlLoader->setPrism('test_prism', ['load' => []]);

        $registry = $this->createRegistry([$phpPrism], $yamlLoader);

        $result = $registry->get(PrismName::fromString('test_prism'));

        $this->assertSame($phpPrism, $result);
    }

    public function testGetCachesYamlPrism(): void
    {
        $yamlLoader = new FakePrismLoader();
        $yamlLoader->setPrism('yaml_prism', ['load' => []]);

        $registry = $this->createRegistry([], $yamlLoader);

        // Premier appel : charge depuis YAML
        $result1 = $registry->get(PrismName::fromString('yaml_prism'));
        // Deuxième appel : doit utiliser le cache
        $result2 = $registry->get(PrismName::fromString('yaml_prism'));

        $this->assertNotNull($result1);
        $this->assertSame($result1, $result2);
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(InMemoryPrismRegistry::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testAllIncludesYamlPrismsFromDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/prisms_test_' . uniqid();
        mkdir($tempDir);

        try {
            // Créer des fichiers YAML temporaires
            file_put_contents($tempDir . '/yaml_prism_1.yaml', "load:\n  - table: users");
            file_put_contents($tempDir . '/yaml_prism_2.yaml', "load:\n  - table: posts");

            $yamlLoader = new FakePrismLoader();
            $yamlLoader->setDirectory($tempDir);
            $yamlLoader->setPrism('yaml_prism_1', ['load' => [['table' => 'users']]]);
            $yamlLoader->setPrism('yaml_prism_2', ['load' => [['table' => 'posts']]]);

            $phpPrism = new FakePrism(PrismName::fromString('php_prism'));
            $registry = $this->createRegistry([$phpPrism], $yamlLoader);

            $all = $registry->all();

            $this->assertGreaterThanOrEqual(3, count($all), 'Devrait avoir 1 PHP + 2 YAML');
            $this->assertContains($phpPrism, $all);
        } finally {
            // Nettoyage
            @unlink($tempDir . '/yaml_prism_1.yaml');
            @unlink($tempDir . '/yaml_prism_2.yaml');
            @rmdir($tempDir);
        }
    }

    public function testAllHandlesInvalidYamlFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/prisms_test_invalid_' . uniqid();
        mkdir($tempDir);

        try {
            // Créer un fichier YAML qui va échouer au chargement
            file_put_contents($tempDir . '/invalid_prism.yaml', "invalid yaml content");
            // Créer un fichier avec un nom qui va provoquer une exception dans PrismName::fromString()
            file_put_contents($tempDir . '/Invalid Prism Name.yaml', "load: []");

            $yamlLoader = new FakePrismLoader();
            $yamlLoader->setDirectory($tempDir);
            // Ne pas définir le scénario dans le loader, il retournera null

            $registry = $this->createRegistry([], $yamlLoader);

            $all = $registry->all();

            // Ne devrait pas planter, juste ignorer les fichiers invalides
            $this->assertIsArray($all);
        } finally {
            @unlink($tempDir . '/invalid_prism.yaml');
            @unlink($tempDir . '/Invalid Prism Name.yaml');
            @rmdir($tempDir);
        }
    }

    public function testHasReturnsTrueForYamlPrism(): void
    {
        $yamlLoader = new FakePrismLoader();
        $yamlLoader->setPrism('yaml_prism', ['load' => []]);

        $registry = $this->createRegistry([], $yamlLoader);

        $this->assertTrue($registry->has(PrismName::fromString('yaml_prism')));
    }

    /**
     * @param array<FakePrism> $prisms
     */
    private function createRegistry(
        array $prisms = [],
        ?FakePrismLoader $yamlLoader = null
    ): InMemoryPrismRegistry {
        $yamlLoader ??= new FakePrismLoader();

        return new InMemoryPrismRegistry(
            $prisms,
            $yamlLoader,
            new FakePrismDataRepository(),
            new FakePrismResourceTracker(),
            new FakeFakeDataGenerator(),
            new FakeDatabaseNameResolver(),
            new FakeLogger(),
        );
    }
}
