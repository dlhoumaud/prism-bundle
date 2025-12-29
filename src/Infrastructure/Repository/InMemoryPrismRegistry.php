<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Repository;

use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Application\Contract\PrismLoaderInterface;
use Prism\Application\Prism\YamlPrism;
use Prism\Domain\Contract\PrismInterface;
use Prism\Domain\Contract\PrismRegistryInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\Contract\FakeDataGeneratorInterface;
use Prism\Domain\Contract\DatabaseNameResolverInterface;
use Prism\Domain\ValueObject\PrismName;
use Psr\Log\LoggerInterface;

/**
 * Repository : Registre en mémoire des scénarios disponibles
 *
 * Utilise le système de tags Symfony pour auto-découvrir tous les scénarios.
 * En fallback, cherche les fichiers YAML dans le dossier prism/
 */
final class InMemoryPrismRegistry implements PrismRegistryInterface
{
    /**
     * @var array<string, PrismInterface>
     */
    private array $prisms = [];

    /**
     * @param iterable<PrismInterface> $prisms
     */
    public function __construct(
        iterable $prisms,
        private readonly PrismLoaderInterface $yamlLoader,
        private readonly PrismDataRepositoryInterface $repository,
        private readonly PrismResourceTrackerInterface $tracker,
        private readonly FakeDataGeneratorInterface $fakeGenerator,
        private readonly DatabaseNameResolverInterface $dbNameResolver,
        private readonly LoggerInterface $logger
    ) {
        foreach ($prisms as $prism) {
            $this->prisms[$prism->getName()->toString()] = $prism;
        }
    }

    public function get(PrismName $name): ?PrismInterface
    {
        $prismName = $name->toString();

        // 1. Priorité : classe PHP
        if (isset($this->prisms[$prismName])) {
            return $this->prisms[$prismName];
        }

        // 2. Fallback : fichier YAML
        $yamlDefinition = $this->yamlLoader->load($prismName);
        if ($yamlDefinition !== null) {
            // Créer une instance YamlPrism à la volée
            $yamlPrism = new YamlPrism(
                $name,
                $this->yamlLoader,
                $this->repository,
                $this->tracker,
                $this->fakeGenerator,
                $this->dbNameResolver,
                $this->logger
            );

            // Cache pour les prochains appels
            $this->prisms[$prismName] = $yamlPrism;

            return $yamlPrism;
        }

        return null;
    }

    public function all(): array
    {
        // Récupérer les scénarios PHP enregistrés
        $allPrisms = array_values($this->prisms);

        // Scanner le dossier prism/yaml/ pour les fichiers YAML (récursivement)
        $yamlDir = $this->yamlLoader->getDirectory();
        if (is_dir($yamlDir)) {
            $yamlFiles = $this->scanYamlFilesRecursively($yamlDir);
            foreach ($yamlFiles as $yamlFile) {
                // Extraire le nom du scénario relativement au dossier yaml/
                $prismName = $this->extractPrismNameFromPath($yamlFile, $yamlDir);

                // Si pas déjà enregistré (classe PHP prioritaire)
                if (!isset($this->prisms[$prismName])) {
                    try {
                        $prism = $this->get(PrismName::fromString($prismName));
                        if ($prism !== null) {
                            $allPrisms[] = $prism;
                        }
                    } catch (\Throwable $e) {
                        // Ignorer les fichiers YAML invalides
                        continue;
                    }
                }
            }
        }

        return $allPrisms;
    }

    /**
     * Scanner récursivement les fichiers YAML
     *
     * @return array<int, string>
     */
    private function scanYamlFilesRecursively(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'yaml') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Extraire le nom du scénario depuis le chemin du fichier
     *
     * Exemples:
     * - prism/yaml/test.yaml → test
     * - prism/yaml/admin/create.yaml → admin/create
     */
    private function extractPrismNameFromPath(string $filePath, string $baseDir): string
    {
        $relativePath = substr($filePath, strlen($baseDir) + 1);
        return str_replace('.yaml', '', $relativePath);
    }

    public function has(PrismName $name): bool
    {
        return $this->get($name) !== null;
    }
}
