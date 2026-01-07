<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Symfony\Yaml;

use Prism\Application\Contract\PrismLoaderInterface;
use Prism\Domain\Contract\FakeDataGeneratorInterface;
use Symfony\Component\Yaml\Yaml;
use RuntimeException;
use DateTimeImmutable;
use DateTime;
use DateTimeInterface;

/**
 * Charge et parse les scénarios au format YAML
 *
 * Supporte les placeholders :
 * - {{ scope }} : remplacé par la valeur du scope
 * - {{ hash('value') }} : génère un hash bcrypt d'une chaîne littérale
 * - {{ hash(scope) }} : génère un hash bcrypt du scope
 * - {{ hash($varname) }} : génère un hash bcrypt d'une variable
 * - {{ env(VAR) }} : récupère une variable d'environnement
 * - {{ now }} : timestamp actuel
 * - {{ date(modifier) }} : date avec modificateur relatif
 * - {{ uuid }} : génère un UUID
 * - {{ $varname }} : référence une variable définie dans la section vars
 * - {{ math(expression) }} : évalue une expression mathématique
 * - {{ fake(type, params...) }} : génère des données aléatoires
 */
final class YamlPrismLoader implements PrismLoaderInterface
{
    private const PLACEHOLDER_PATTERN = '/\{\{\s*([^}]+)\s*\}\}/';

    /**
     * @var array<string, string>
     */
    private array $variables = [];

    /**
     * @var array<string, mixed> Variables temporaires (champs data du bloc en cours)
     */
    private array $temporaryVariables = [];

    public function __construct(
        private readonly string $yamlDirectory,
        private readonly FakeDataGeneratorInterface $fakeGenerator
    ) {
    }

    /**
     * Récupère le chemin du dossier des scénarios
     */
    public function getDirectory(): string
    {
        return $this->yamlDirectory;
    }

    /**
     * Charge un fichier YAML de scénario
     *
     * Supporte les chemins avec sous-dossiers:
     * - load('test') → prism/yaml/test.yaml
     * - load('admin/create') → prism/yaml/admin/create.yaml
     *
     * @return array{load: array, purge?: array, vars?: array}|null
     */
    public function load(string $prismName): ?array
    {
        $filePath = sprintf('%s/%s.yaml', $this->yamlDirectory, $prismName);

        if (!file_exists($filePath)) {
            return null;
        }

        return $this->loadFile($filePath);
    }

    /**
     * Charge un fichier YAML avec support des imports
     *
     * @return array{load: array, purge?: array, vars?: array}
     */
    private function loadFile(string $filePath, array $importedFiles = []): array
    {
        // Protection contre les imports circulaires
        if (in_array($filePath, $importedFiles, true)) {
            throw new RuntimeException(sprintf('Circular import detected: %s', $filePath));
        }
        $importedFiles[] = $filePath;

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to read prism file: %s', $filePath));
        }

        $parsed = Yaml::parse($content);

        if (!is_array($parsed)) {
            throw new RuntimeException(sprintf('Invalid YAML format in: %s', $filePath));
        }

        // Traiter les imports
        $result = ['load' => [], 'purge' => [], 'vars' => []];

        if (isset($parsed['import']) && is_array($parsed['import'])) {
            foreach ($parsed['import'] as $importName) {
                // Les imports sont TOUJOURS relatifs au dossier yaml racine
                $importPath = sprintf('%s/%s.yaml', $this->yamlDirectory, $importName);

                if (!file_exists($importPath)) {
                    throw new RuntimeException(sprintf('Import file not found: %s (looking in %s)', $importName, $this->yamlDirectory));
                }

                $importedData = $this->loadFile($importPath, $importedFiles);

                // Fusionner vars (les imports sont prioritaires)
                $result['vars'] = array_merge($result['vars'], $importedData['vars'] ?? []);

                // Concaténer load (imports en premier)
                $result['load'] = array_merge($result['load'], $importedData['load']);

                // Concaténer purge (imports en premier, mais l'ordre sera inversé lors du purge)
                $result['purge'] = array_merge($result['purge'], $importedData['purge'] ?? []);
            }
        }

        // Fusionner avec le contenu local (local override pour vars)
        if (isset($parsed['vars']) && is_array($parsed['vars'])) {
            $result['vars'] = array_merge($result['vars'], $parsed['vars']);
        }

        // Concaténer load local (après les imports)
        if (isset($parsed['load']) && is_array($parsed['load'])) {
            $result['load'] = array_merge($result['load'], $parsed['load']);
        }

        // Concaténer purge local (après les imports)
        // L'ordre d'exécution sera inversé dans YamlPrism::purge()
        if (isset($parsed['purge']) && is_array($parsed['purge'])) {
            $result['purge'] = array_merge($result['purge'], $parsed['purge']);
        }

        // Valider qu'on a au moins un load
        if (empty($result['load'])) {
            throw new RuntimeException(sprintf('Missing "load" section in: %s', $filePath));
        }

        // Nettoyer les sections vides
        if (empty($result['vars'])) {
            unset($result['vars']);
        }
        if (empty($result['purge'])) {
            unset($result['purge']);
        }
        return $result;
    }

    /**
     * Initialise les variables depuis la définition YAML
     *
     * Les variables sont déclarées sans $ mais utilisées avec $ dans les templates
     * Exemple: vars: { user: "john" } → utilisation: {{ $user }}
     *
     * @param array<string, string> $vars
     * @param string $scope
     */
    public function initializeVariables(array $vars, string $scope): void
    {
        $this->variables = [];

        foreach ($vars as $varName => $varValue) {
            // Si la variable est déclarée avec $, on l'accepte mais on le retire
            $cleanVarName = str_starts_with((string) $varName, '$') ? substr((string) $varName, 1) : (string) $varName;

            // Résoudre les placeholders (retourne toujours une string)
            /** @var string $resolvedValue */
            $resolvedValue = $this->processPlaceholder(
                (string) $varValue,
                $scope
            );

            // Stocker avec le $ pour la résolution
            $this->variables['$' . $cleanVarName] = $resolvedValue;
        }
    }

    /**
     * Réinitialise les variables
     */
    public function resetVariables(): void
    {
        $this->variables = [];
    }

    /**
     * Démarre un nouveau bloc de variables temporaires
     */
    public function startTemporaryScope(): void
    {
        $this->temporaryVariables = [];
    }

    /**
     * Ajoute une variable temporaire (créée depuis un champ data)
     */
    public function addTemporaryVariable(string $fieldName, mixed $value): void
    {
        // Créer une variable avec le nom du champ (sans $)
        $this->temporaryVariables['$' . $fieldName] = $value;
    }

    /**
     * Réinitialise les variables temporaires après traitement du bloc
     */
    public function resetTemporaryVariables(): void
    {
        $this->temporaryVariables = [];
    }

    /**
     * Ajoute une variable globale à la volée (ex: var: name_user)
     * Le nom peut être fourni avec ou sans le préfixe `$`.
     */
    public function addVariable(string $name, mixed $value, string $scope): void
    {
        $cleanName = str_starts_with((string) $name, '$') ? substr((string) $name, 1) : (string) $name;

            // Résoudre les placeholders (retourne toujours une string)
            /** @var string $resolvedValue */
            $resolvedValue = $this->processPlaceholder(
                (string) $value,
                $scope
            );

        // Convertir en string pour rester compatible avec le comportement
        // des variables initialisées depuis la section vars
        $this->variables['$' . $cleanName] = $resolvedValue;
    }

    /**
     * Remplace les placeholders dans les données
     * Mode séquentiel : chaque champ résolu devient une variable temporaire
     *
     * @param array<string, mixed> $data
     * @param string $scope
     * @return array<string, mixed>
     */
    public function replacePlaceholders(array $data, string $scope): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Ne pas créer de variable temporaire pour les arrays (lookups, etc.)
                $result[$key] = $this->replacePlaceholders($value, $scope);
            } elseif (is_string($value)) {
                $resolved = $this->processPlaceholder($value, $scope);
                $result[$key] = $resolved;

                // Créer une variable temporaire avec le nom du champ
                $this->addTemporaryVariable($key, $resolved);
            } else {
                $result[$key] = $value;

                // Créer une variable temporaire même pour les valeurs non-string
                $this->addTemporaryVariable($key, $value);
            }
        }

        return $result;
    }

    /**
     * Traite un placeholder dans une chaîne
     */
    private function processPlaceholder(string $value, string $scope): mixed
    {
        $callback = function (array $matches) use ($scope): string {
            $fullExpression = trim($matches[1]);

            // Séparer l'expression de base et les pipes
            // Exemple: "hash(scope)|truncate(24)|replace('a', 'b')"
            $parts = explode('|', $fullExpression);
            $expression = trim(array_shift($parts));
            $pipes = $parts;

                // {{ $varname }} - Variable personnalisée (temporaire prioritaire sur globale)
            if (str_starts_with($expression, '$')) {
                // Chercher d'abord dans les variables temporaires (champs du bloc courant)
                if (isset($this->temporaryVariables[$expression])) {
                    return $this->applyPipes($this->temporaryVariables[$expression], $pipes);
                }

                // Puis dans les variables globales (vars:)
                if (isset($this->variables[$expression])) {
                    return $this->applyPipes($this->variables[$expression], $pipes);
                }

                throw new RuntimeException(sprintf(
                    'Undefined variable: %s. Make sure it is defined in the vars section '
                    . 'or as a previous field in data.',
                    $expression
                ));
            }

                // {{ scope }}
            if ($expression === 'scope') {
                return $this->applyPipes($scope, $pipes);
            }

                // {{ hash(value) }} - Support de chaînes, scope et variables
            if (preg_match('/^hash\((.+)\)$/', $expression, $hashMatches)) {
                $hashArg = trim($hashMatches[1]);
                $valueToHash = null;

                // Cas 1: hash(scope)
                if ($hashArg === 'scope') {
                    $valueToHash = $scope;
                } elseif (str_starts_with($hashArg, '$')) {
                    // Cas 2: hash($varname) - Variable
                    // Chercher d'abord dans les variables temporaires
                    if (isset($this->temporaryVariables[$hashArg])) {
                        $valueToHash = $this->temporaryVariables[$hashArg];
                    } elseif (isset($this->variables[$hashArg])) {
                        // Puis dans les variables globales
                        $valueToHash = $this->variables[$hashArg];
                    } else {
                        throw new RuntimeException(sprintf(
                            'Undefined variable in hash(): %s',
                            $hashArg
                        ));
                    }
                } elseif (preg_match('/^[\'"](.+)[\'"]$/', $hashArg, $literalMatches)) {
                    // Cas 3: hash('value') ou hash("value") - Chaîne littérale
                    $valueToHash = $literalMatches[1];
                } else {
                    throw new RuntimeException(sprintf(
                        'Invalid hash() argument: %s. Use scope, $variable or "string"',
                        $hashArg
                    ));
                }

                $hash = password_hash((string) $valueToHash, PASSWORD_BCRYPT);
                return $this->applyPipes($hash, $pipes);
            }

                // {{ env(VAR) }}
            if (preg_match('/^env\([\'"](.+)[\'"]\)$/', $expression, $envMatches)) {
                $envVar = $envMatches[1];

                // Chercher dans $_ENV, $_SERVER puis getenv()
                $value = $_ENV[$envVar] ?? $_SERVER[$envVar] ?? getenv($envVar);

                if ($value === false || $value === null) {
                    throw new RuntimeException(sprintf(
                        'Environment variable not found: %s',
                        $envVar
                    ));
                }

                return $this->applyPipes($value, $pipes);
            }

                // {{ now }}
            if ($expression === 'now') {
                return $this->applyPipes((new DateTimeImmutable())->format('Y-m-d H:i:s'), $pipes);
            }

                // {{ date(modifier) }}
            if (preg_match('/^date\([\'"](.+)[\'"]\)$/', $expression, $dateMatches)) {
                $modifier = $dateMatches[1];

                try {
                    $timestamp = strtotime($modifier);

                    if ($timestamp === false) {
                        throw new RuntimeException(sprintf(
                            'Invalid date modifier: %s',
                            $modifier
                        ));
                    }

                    $dateString = (new DateTimeImmutable('@' . $timestamp))->format('Y-m-d H:i:s');
                    return $this->applyPipes($dateString, $pipes);
                } catch (\Throwable $e) {
                    throw new RuntimeException(sprintf(
                        'Error parsing date modifier "%s": %s',
                        $modifier,
                        $e->getMessage()
                    ));
                }
            }

                // {{ uuid }}
            if ($expression === 'uuid') {
                $uuid = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff)
                );
                return $this->applyPipes($uuid, $pipes);
            }

                // {{ math(expression) }}
            if (preg_match('/^math\((.+)\)$/', $expression, $mathMatches)) {
                $result = $this->evaluateMath($mathMatches[1]);
                return $this->applyPipes($result, $pipes);
            }

                // {{ fake(type) }} ou {{ fake(type, param1, param2, ...) }}
            if (preg_match('/^fake\(([^)]+)\)$/', $expression, $fakeMatches)) {
                $args = array_map('trim', explode(',', $fakeMatches[1]));

                // Retirer les quotes du premier argument (type)
                $type = trim($args[0], '\'"');

                // Les autres arguments sont des paramètres
                $params = array_slice($args, 1);
                $params = array_map(fn($p) => trim($p, '\'"'), $params);

                $fakeValue = (string)$this->fakeGenerator->generate($type, $scope, ...$params);
                return $this->applyPipes($fakeValue, $pipes);
            }

                // Placeholder inconnu, on laisse tel quel
                return $matches[0];
        };

        /** @phpstan-ignore-next-line */
        return (string) preg_replace_callback(self::PLACEHOLDER_PATTERN, $callback, $value);
    }

    /**
     * Applique une série de pipes à une valeur
     */
    private function applyPipes(mixed $value, array $pipes): string
    {
        foreach ($pipes as $pipe) {
            $pipe = trim($pipe);

            // Parser le pipe : name ou name(arg1, arg2, ...)
            if (preg_match('/^(\w+)(?:\((.*)\))?$/', $pipe, $pipeMatches)) {
                $pipeName = $pipeMatches[1];
                $args = isset($pipeMatches[2]) && $pipeMatches[2] !== ''
                    ? $this->parsePipeArgs($pipeMatches[2])
                    : [];

                $value = $this->executePipe($pipeName, $value, $args);
            }
        }

        return (string) $value;
    }

    /**
     * Parse les arguments d'un pipe
     */
    private function parsePipeArgs(string $argsString): array
    {
        $args = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;

        for ($i = 0; $i < strlen($argsString); $i++) {
            $char = $argsString[$i];

            if (($char === '"' || $char === "'") && ($i === 0 || $argsString[$i - 1] !== '\\')) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                }
                continue;
            }

            if ($char === ',' && !$inQuotes) {
                $args[] = $this->normalizeArg(trim($current));
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $args[] = $this->normalizeArg(trim($current));
        }

        return $args;
    }

    /**
     * Normalise un argument (convertir string en int si numérique)
     */
    private function normalizeArg(string $arg): mixed
    {
        if (is_numeric($arg)) {
            return str_contains($arg, '.') ? (float) $arg : (int) $arg;
        }

        return $arg;
    }

    /**
     * Exécute un pipe sur une valeur
     */
    private function executePipe(string $pipe, mixed $value, array $args): mixed
    {
        return match ($pipe) {
            'truncate' => $this->pipeTruncate($value, $args),
            'trim' => $this->pipeTrim($value),
            'uppercase', 'upper' => $this->pipeUppercase($value),
            'lowercase', 'lower' => $this->pipeLowercase($value),
            'capitalize', 'ucfirst' => $this->pipeCapitalize($value),
            'replace' => $this->pipeReplace($value, $args),
            'base64' => base64_encode((string) $value),
            'md5' => md5((string) $value),
            'sha1' => sha1((string) $value),
            'htmlencode' => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'urlencode' => urlencode((string) $value),
            default => throw new RuntimeException(sprintf(
                'Unknown pipe: %s. Available pipes: truncate, trim, uppercase, lowercase, capitalize,'
                . 'replace, base64, md5, sha1, htmlencode, urlencode',
                $pipe
            )),
        };
    }

    /**
     * Pipe: truncate(max) - Limite la longueur de la chaîne
     */
    private function pipeTruncate(mixed $value, array $args): string
    {
        if (count($args) < 1) {
            throw new RuntimeException('Pipe truncate() requires 1 argument: max length');
        }

        $max = (int) $args[0];
        return substr((string) $value, 0, $max);
    }

    /**
     * Pipe: trim - Supprime les espaces
     */
    private function pipeTrim(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * Pipe: uppercase - Convertit en majuscules
     */
    private function pipeUppercase(mixed $value): string
    {
        return strtoupper((string) $value);
    }

    /**
     * Pipe: lowercase - Convertit en minuscules
     */
    private function pipeLowercase(mixed $value): string
    {
        return strtolower((string) $value);
    }

    /**
     * Pipe: capitalize - Première lettre en majuscule
     */
    private function pipeCapitalize(mixed $value): string
    {
        return ucfirst((string) $value);
    }

    /**
     * Pipe: replace(search, replace) - Remplace une chaîne
     */
    private function pipeReplace(mixed $value, array $args): string
    {
        if (count($args) < 2) {
            throw new RuntimeException('Pipe replace() requires 2 arguments: search and replace');
        }

        return str_replace($args[0], $args[1], (string) $value);
    }

    /**
     * Évalue une expression mathématique de manière sécurisée
     * Supporte : +, -, *, /, %, parenthèses, variables ($var)
     */
    private function evaluateMath(string $expression): float|int
    {
        // Remplacer les variables par leurs valeurs (temporaires prioritaires)
        $expression = preg_replace_callback(
            '/\$(\w+)/',
            function (array $matches): string {
                $varName = '$' . $matches[1];

                // Chercher d'abord dans les variables temporaires
                if (isset($this->temporaryVariables[$varName])) {
                    $value = $this->temporaryVariables[$varName];
                } elseif (isset($this->variables[$varName])) {
                    $value = $this->variables[$varName];
                } else {
                    throw new RuntimeException(sprintf(
                        'Undefined variable in math expression: %s',
                        $varName
                    ));
                }

                if (!is_numeric($value)) {
                    throw new RuntimeException(sprintf(
                        'Variable %s is not numeric: %s',
                        $varName,
                        var_export($value, true)
                    ));
                }
                return (string) $value;
            },
            $expression
        );

        // Nettoyer l'expression (supprimer les espaces)
        $expression = str_replace(' ', '', (string) $expression);

        // Validation : uniquement des caractères autorisés
        if (!preg_match('/^[0-9+\-*\/.()%]+$/', $expression)) {
            throw new RuntimeException(sprintf(
                'Invalid math expression: %s (only numbers and operators +, -, *, /, %%, () are allowed)',
                $expression
            ));
        }

        // Empêcher les divisions par zéro évidentes
        if (preg_match('/\/0(?![0-9.])/', $expression)) {
            throw new RuntimeException('Division by zero in math expression');
        }

        // Évaluer l'expression de manière sécurisée
        try {
            $result = eval("return ($expression);");

            if ($result === false) {
                throw new RuntimeException('Failed to evaluate math expression');
            }

            return $result;
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf(
                'Error evaluating math expression "%s": %s',
                $expression,
                $e->getMessage()
            ));
        }
    }

    /**
     * Convertit les types spéciaux (datetime_immutable, etc.)
     *
     * @param mixed $value
     */
    public function convertType(mixed $value, ?string $type = null): mixed
    {
        if ($type === null) {
            return $value;
        }

        return match ($type) {
            'datetime_immutable' => $this->convertToDateTime($value, true),
            'datetime' => $this->convertToDateTime($value, false),
            'datetimetz' => $this->convertToDateTime($value, false),
            'datetimetz_immutable' => $this->convertToDateTime($value, true),
            'date' => $this->convertToDateTime($value, false),
            'date_immutable' => $this->convertToDateTime($value, true),
            'time' => $this->convertToDateTime($value, false),
            'time_immutable' => $this->convertToDateTime($value, true),
            'int', 'integer' => $this->convertToInt($value, -2147483648, 2147483647),
            'smallint' => $this->convertToInt($value, -32768, 32767),
            'bigint' => $this->convertToInt($value, PHP_INT_MIN, PHP_INT_MAX),
            'float' => is_numeric($value) ? (float) $value : 0.0,
            'decimal' => is_numeric($value) ? (string) $value : '0',
            'bool', 'boolean' => (bool) $value,
            'string', 'text' => is_string($value) || is_numeric($value) ? (string) $value : '',
            'guid', 'uuid' => is_string($value) ? $value : '',
            'json' => $this->convertToJson($value),
            'array' => is_string($value) ? unserialize($value) : (is_array($value) ? $value : []),
            'simple_array' => is_string($value) ? array_filter(array_map('trim', explode(',', $value))) : (is_array($value) ? $value : []),
            'binary', 'blob' => $value,
            default => $value,
        };
    }

    /**
     * Convertit une valeur en entier en respectant les limites min/max
     * Tronque automatiquement si la valeur dépasse les limites
     */
    private function convertToInt(mixed $value, int $min, int $max): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        $intValue = (int) $value;

        // Clamp la valeur entre min et max
        return max($min, min($max, $intValue));
    }

    /**
     * Convertit une valeur en DateTime ou DateTimeImmutable
     * Gère les strings, timestamps, conversions DateTime ↔ DateTimeImmutable
     */
    private function convertToDateTime(mixed $value, bool $immutable): DateTime|DateTimeImmutable|null
    {
        // Déjà le bon type
        if ($immutable && $value instanceof DateTimeImmutable) {
            return $value;
        }
        if (!$immutable && $value instanceof DateTime) {
            // Vérifier que ce n'est pas un DateTimeImmutable déguisé
            if ($value::class === DateTime::class) {
                return $value;
            }
        }

        // Conversion DateTime ↔ DateTimeImmutable
        if ($value instanceof DateTimeInterface) {
            return $immutable
                ? DateTimeImmutable::createFromInterface($value)
                : DateTime::createFromInterface($value);
        }

        // String : parser la date
        if (is_string($value)) {
            try {
                return $immutable
                    ? new DateTimeImmutable($value)
                    : new DateTime($value);
            } catch (\Exception $e) {
                // String invalide, retourner null
                return null;
            }
        }

        // Int : timestamp
        if (is_int($value)) {
            return $immutable
                ? (new DateTimeImmutable())->setTimestamp($value)
                : (new DateTime())->setTimestamp($value);
        }

        // Autres types : retourner null
        return null;
    }

    /**
     * Convertit une valeur en JSON (array)
     * Si c'est une string, décode le JSON avec vérification d'erreur
     * Retourne un array vide si le décodage échoue
     */
    private function convertToJson(mixed $value): mixed
    {
        // Si c'est déjà un array, retourner tel quel
        if (is_array($value)) {
            return $value;
        }

        // Si c'est une string, essayer de décoder
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            // Si erreur de décodage, retourner array vide au lieu d'exception
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            return $decoded;
        }

        // Pour les autres types, retourner array vide
        return [];
    }
}
