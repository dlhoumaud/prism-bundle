<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Prism\Application\Contract\PrismLoaderInterface;

/**
 * Fake Repository : Loader de scénarios pour les tests
 */
class FakePrismLoader implements PrismLoaderInterface
{
    private array $prisms = [];
    private array $variables = [];
    private array $temporaryVariables = [];
    private int $replacePlaceholdersCallCount = 0;
    private int $convertTypeCallCount = 0;
    private string $directory = '/fake/prisms';
    private array $yamlFiles = [];
    private array $invalidPlaceholderResolutions = [];

    public function load(string $prismName): ?array
    {
        return $this->prisms[$prismName] ?? null;
    }

    public function setPrism(string $name, array $definition): void
    {
        $this->prisms[$name] = $definition;
    }

    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    public function addYamlFile(string $filename): void
    {
        $this->yamlFiles[] = $filename;
    }

    public function getYamlFiles(): array
    {
        return $this->yamlFiles;
    }

    public function initializeVariables(array $vars, string $scope): void
    {
        foreach ($vars as $key => $value) {
            $this->variables[$key] = str_replace('{{ scope }}', $scope, $value);
        }
    }

    public function resetVariables(): void
    {
        $this->variables = [];
    }

    public function startTemporaryScope(): void
    {
        $this->temporaryVariables = [];
    }

    public function resetTemporaryVariables(): void
    {
        $this->temporaryVariables = [];
    }

    public function addTemporaryVariable(string $fieldName, mixed $value): void
    {
        // Fake implementation
        $this->temporaryVariables[$fieldName] = $value;
    }

    public function addVariable(string $name, mixed $value, string $scope): void
    {
        // Simulate variable initialization: replace scope placeholder if present
        if (is_string($value) || is_numeric($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $this->variables[$name] = str_replace('{{ scope }}', $scope, (string) $value);
        } else {
            $this->variables[$name] = $value;
        }
    }

    public function replacePlaceholders(array $data, string $scope): array
    {
        $this->replacePlaceholdersCallCount++;

        // Gérer les résolutions invalides pour les tests
        foreach ($this->invalidPlaceholderResolutions as $key => $invalidValue) {
            if (isset($data[$key])) {
                return [$key => $invalidValue];
            }
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Traiter les expressions {{ ... }}
                $value = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) use ($scope) {
                    $fullExpression = trim($matches[1]);

                    // Séparer l'expression de base et les pipes
                    $baseExpression = $fullExpression;
                    $pipes = [];

                    // Vérifier si c'est hash(...)|pipe1|pipe2 - ne pas split dans les parenthèses
                    if (str_contains($fullExpression, '|')) {
                        // Trouver la fin de hash() si présent
                        if (preg_match('/^(hash\([^)]+\))(.*)$/', $fullExpression, $hashPipeMatches)) {
                            $baseExpression = $hashPipeMatches[1];
                            $pipePart = ltrim($hashPipeMatches[2], '|');
                            if ($pipePart !== '') {
                                $pipes = array_map('trim', explode('|', $pipePart));
                            }
                        } else {
                            // Pas de hash(), on peut splitter normalement
                            $parts = explode('|', $fullExpression);
                            $baseExpression = trim($parts[0]);
                            $pipes = array_map('trim', array_slice($parts, 1));
                        }
                    }

                    // Évaluer l'expression de base
                    $result = null;

                    // hash(...)
                    if (preg_match('/^hash\((.+)\)$/', $baseExpression, $hashMatches)) {
                        $hashArg = trim($hashMatches[1]);

                        if ($hashArg === 'scope') { // hash(scope)
                            $result = password_hash($scope, PASSWORD_BCRYPT);
                        } elseif (str_starts_with($hashArg, '$')) { // hash($variable)
                            $varName = ltrim($hashArg, '$');
                            if (isset($this->variables[$varName])) {
                                $result = password_hash($this->variables[$varName], PASSWORD_BCRYPT);
                            } elseif (isset($this->temporaryVariables[$varName])) {
                                $result = password_hash($this->temporaryVariables[$varName], PASSWORD_BCRYPT);
                            } else {
                                throw new \RuntimeException(sprintf('Undefined variable in hash(): $%s', $varName));
                            }
                        } elseif (preg_match('/^[\'"](.+)[\'"]$/', $hashArg, $literalMatches)) { // hash("literal")
                            $result = password_hash($literalMatches[1], PASSWORD_BCRYPT);
                        } else {
                            throw new \RuntimeException(sprintf('Invalid hash argument: %s', $hashArg));
                        }
                    } elseif ($baseExpression === 'scope') { // scope
                        $result = $scope;
                    } elseif (str_starts_with($baseExpression, '$')) { // $variable
                        $varName = ltrim($baseExpression, '$');
                        $result = $this->variables[$varName] ?? $this->temporaryVariables[$varName] ?? null;
                    } elseif (isset($this->variables[$baseExpression])) { // Variable simple (vars)
                        $result = $this->variables[$baseExpression];
                    } elseif ($baseExpression === 'now') { // now
                        $result = date('Y-m-d H:i:s');
                    }

                    // Si aucune correspondance, retourner l'original
                    if ($result === null) {
                        return $matches[0];
                    }

                    // Appliquer les pipes
                    foreach ($pipes as $pipe) {
                        $result = $this->applyPipe($pipe, $result);
                    }

                    return $result;
                }, $value);

                // Stocker comme variable temporaire
                $this->temporaryVariables[$key] = $value;
            }
            $result[$key] = $value;
        }

        return $result;
    }

    private function applyPipe(string $pipe, string $value): string
    {
        // Séparer le nom du pipe et ses arguments
        if (preg_match('/^([a-zA-Z_]+)\s*\((.*)\)$/', $pipe, $matches)) {
            $pipeName = $matches[1];
            $argsString = $matches[2];

            // Parser les arguments
            $args = [];
            if (trim($argsString) !== '') {
                $args = array_map('trim', explode(',', $argsString));
            }
        } else {
            $pipeName = $pipe;
            $args = [];
        }

        if ($pipeName === 'truncate') {
            return $this->truncatePipe($value, $args);
        } elseif ($pipeName === 'trim') {
            return trim($value);
        } elseif ($pipeName === 'uppercase' || $pipeName === 'upper') {
            return strtoupper($value);
        } elseif ($pipeName === 'lowercase' || $pipeName === 'lower') {
            return strtolower($value);
        } elseif ($pipeName === 'capitalize' || $pipeName === 'ucfirst') {
            return ucfirst($value);
        } elseif ($pipeName === 'replace') {
            return $this->replacePipe($value, $args);
        } else {
            throw new \RuntimeException(sprintf('Unknown pipe: %s', $pipeName));
        }
    }

    private function truncatePipe(string $value, array $args): string
    {
        if (empty($args)) {
            throw new \RuntimeException('Pipe truncate() requires 1 argument: max length');
        }

        $maxLength = (int) $args[0];
        return mb_substr($value, 0, $maxLength);
    }

    private function replacePipe(string $value, array $args): string
    {
        if (count($args) < 2) {
            throw new \RuntimeException('Pipe replace() requires 2 arguments: search and replace');
        }

        $search = trim($args[0], '\'"');
        $replace = trim($args[1], '\'"');

        return str_replace($search, $replace, $value);
    }

    public function convertType(mixed $value, ?string $type = null): mixed
    {
        $this->convertTypeCallCount++;

        if ($type === 'datetime_immutable' && is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        return $value;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getReplacePlaceholdersCallCount(): int
    {
        return $this->replacePlaceholdersCallCount;
    }

    public function getConvertTypeCallCount(): int
    {
        return $this->convertTypeCallCount;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setTemporaryVariable(string $name, mixed $value): void
    {
        $this->temporaryVariables[$name] = $value;
    }

    public function setInvalidPlaceholderResolution(string $key, mixed $invalidValue): void
    {
        $this->invalidPlaceholderResolutions[$key] = $invalidValue;
    }

    public function reset(): void
    {
        $this->prisms = [];
        $this->variables = [];
        $this->temporaryVariables = [];
        $this->replacePlaceholdersCallCount = 0;
        $this->convertTypeCallCount = 0;
        $this->directory = '/fake/prisms';
        $this->yamlFiles = [];
        $this->invalidPlaceholderResolutions = [];
    }
}
