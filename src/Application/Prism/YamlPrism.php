<?php

declare(strict_types=1);

namespace Prism\Application\Prism;

use Prism\Application\Contract\PrismDataRepositoryInterface;
use Prism\Application\Contract\PrismLoaderInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
use Prism\Domain\Contract\FakeDataGeneratorInterface;
use Prism\Domain\Contract\DatabaseNameResolverInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Scénario chargé depuis un fichier YAML
 *
 * Exécute les instructions définies dans prism/{name}.yaml
 *
 * Peut être étendu pour ajouter de la logique PHP personnalisée
 * tout en conservant le chargement YAML de base.
 */
class YamlPrism extends AbstractPrism
{
    /**
     * @var array{load: array, purge?: array, vars?: array}
     */
    private array $definition;

    public function __construct(
        private readonly PrismName $name,
        private readonly PrismLoaderInterface $loader,
        PrismDataRepositoryInterface $repository,
        PrismResourceTrackerInterface $tracker,
        FakeDataGeneratorInterface $fakeGenerator,
        DatabaseNameResolverInterface $dbNameResolver,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($repository, $tracker, $fakeGenerator, $dbNameResolver, $logger);

        $definition = $this->loader->load($this->name->toString());
        if ($definition === null) {
            throw new RuntimeException(sprintf(
                'YAML prism file not found: %s.yaml',
                $this->name->toString()
            ));
        }


        $this->definition = $definition;
    }

    public function getName(): PrismName
    {
        return $this->name;
    }

    public function load(Scope $scope): void
    {
        $this->currentScope = $scope;
        $scopeStr = $scope->toString();

        $this->logger->info('Chargement du scénario YAML: {name}', [
            'name' => $this->name->toString(),
            'scope' => $scopeStr
        ]);

        // Initialiser les variables si elles existent
        if (isset($this->definition['vars'])) {
            $this->loader->initializeVariables($this->definition['vars'], $scopeStr);
            $this->logger->debug('Variables initialisées: {count}', [
                'count' => count($this->definition['vars'])
            ]);
        }

        foreach ($this->definition['load'] as $index => $instruction) {
            $this->logger->info('→ Instruction #{index}: {table}', [
                'index' => $index,
                'table' => $instruction['table'] ?? 'unknown'
            ]);
            $this->executeInstruction($instruction, $scope, $index);
        }

        // Réinitialiser les variables après le chargement
        $this->loader->resetVariables();

        $this->logger->info('✓ Scénario YAML chargé: {name}', [
            'name' => $this->name->toString()
        ]);
    }

    public function purge(Scope $scope): void
    {
        $this->currentScope = $scope;
        $pivotPurgeExecuted = false;

        // Initialiser les variables si elles existent (pour le purge aussi)
        if (isset($this->definition['vars'])) {
            $this->loader->initializeVariables($this->definition['vars'], $scope->toString());
        }

        // Si une section purge custom existe dans le YAML
        if (isset($this->definition['purge'])) {
            $this->logger->info('Exécution du purge personnalisé YAML: {name}', [
                'name' => $this->name->toString()
            ]);

            // Inverser l'ordre pour respecter les FK (derniers créés = premiers supprimés)
            $purgeInstructions = array_reverse($this->definition['purge']);

            foreach ($purgeInstructions as $index => $instruction) {
                // Si l'instruction demande le purge automatique (pivot)
                if (isset($instruction['purge_pivot']) && $instruction['purge_pivot'] === true) {
                    $this->logger->info('⚡ Purge automatique (pivot) déclenché à l\'instruction #{index}', [
                        'index' => $index
                    ]);
                    parent::purge($scope);
                    $pivotPurgeExecuted = true;
                } else {
                    // Instruction de purge normale
                    $this->executePurgeInstruction($instruction, $scope, $index);
                }
            }
        }

        // Si le purge pivot n'a pas été exécuté, on l'exécute à la fin
        if (!$pivotPurgeExecuted) {
            $this->logger->info('Purge automatique (pivot) à la fin');
            parent::purge($scope);
        }

        // Réinitialiser les variables après le purge
        $this->loader->resetVariables();
    }

    /**
     * Exécute une instruction de chargement
     *
     * @param array<string, mixed> $instruction
     */
    /**
     * @param array<string, mixed> $instruction
     */
    private function executeInstruction(array $instruction, Scope $scope, int $index): void
    {
        if (!isset($instruction['table']) || !is_string($instruction['table'])) {
            throw new \RuntimeException(sprintf(
                'Missing "table" key in load instruction #%d',
                $index
            ));
        }

        if (!isset($instruction['data']) || !is_array($instruction['data'])) {
            throw new RuntimeException(sprintf(
                'Missing "data" key in load instruction #%d for table "%s"',
                $index,
                $instruction['table']
            ));
        }

        $table = $instruction['table'];

        // Démarrer un nouveau scope de variables temporaires
        $this->loader->startTemporaryScope();

        // Traiter les champs un par un et résoudre les placeholders au fur et à mesure
        $data = $instruction['data'];
        $types = isset($instruction['types']) && is_array($instruction['types']) ? $instruction['types'] : [];

        foreach ($data as $column => $value) {
            // Cas 1: structure { var: name, value: ... } → créer une variable globale
            if (is_array($value) && isset($value['var']) && array_key_exists('value', $value)) {
                $varName = (string) $value['var'];
                $inner = $value['value'];

                // Si la value est elle-même un lookup, la résoudre (resolveLookupReference gère les placeholders internes)
                if (is_array($inner) && $this->isLookupReference($inner)) {
                    /** @var array{table: string, where: array<string, mixed>, return: string, db?: string} $inner */
                    $resolved = $this->resolveLookupReference($inner, $scope);
                } else {
                    // Si c'est une string, remplacer les placeholders maintenant (pour voir les variables déjà créées)
                    if (is_string($inner)) {
                        $tmp = $this->loader->replacePlaceholders(['_v' => $inner], $scope->toString());
                        $resolved = $tmp['_v'];
                    } else {
                        $resolved = $inner;
                    }
                }

                // Remplacer la colonne par la valeur résolue
                $data[$column] = $resolved;

                // Mettre à jour variable temporaire
                $this->loader->addTemporaryVariable($column, $resolved);

                // Créer/mettre à jour la variable globale demandée
                $this->loader->addVariable($varName, $resolved, $scope->toString());

                continue;
            }

            // Cas 2: lookup classique (possiblement accompagné d'une clé 'var' pour exporter)
            if (is_array($value) && $this->isLookupReference($value)) {
                /** @var array{table: string, where: array<string, mixed>, return: string, var?: string} $value */
                $resolved = $this->resolveLookupReference($value, $scope);
                $data[$column] = $resolved;
                // Mettre à jour la variable temporaire avec la valeur résolue
                $this->loader->addTemporaryVariable($column, $data[$column]);

                // Si la configuration de lookup demande l'export en variable globale
                if (isset($value['var'])) {
                    $this->loader->addVariable((string) $value['var'], $resolved, $scope->toString());
                }

                continue;
            }

            // Cas 3: valeur simple (string ou autre) — remplacer les placeholders maintenant
            if (is_string($value) || !is_array($value)) {
                $tmp = $this->loader->replacePlaceholders([$column => $value], $scope->toString());
                $data[$column] = $tmp[$column];
                $this->loader->addTemporaryVariable($column, $data[$column]);
            }
        }

        // Conversion des types si spécifiés
        foreach ($types as $column => $type) {
            if (isset($data[$column]) && is_string($type)) {
                $data[$column] = $this->loader->convertType($data[$column], $type);
            }
        }

        $this->logger->debug('Insertion dans {table}', [
            'table' => $table,
            'instruction' => $index
        ]);

        // Gestion du pivot custom
        $pivot = isset($instruction['pivot']) && is_array($instruction['pivot']) ? $instruction['pivot'] : null;

        // Récupération de la base de données si définie
        $dbName = isset($instruction['db']) && is_string($instruction['db']) ? $instruction['db'] : null;

        // Résoudre les placeholders dans la valeur de db (variables temporaires/globales)
        if ($dbName !== null) {
            $tmp = $this->loader->replacePlaceholders(['_db' => $dbName], $scope->toString());
            $dbName = $tmp['_db'];

            // Résoudre les alias/placeholder de connexion via le DatabaseNameResolver
            try {
                $resolved = $this->dbNameResolver->resolve($dbName);
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf(
                    'Cannot resolve database name "%s" for instruction on table "%s": %s',
                    $dbName,
                    $table,
                    $e->getMessage()
                ));
            }

            if ($resolved !== null) {
                $dbName = $resolved;
            }
        }

        if ($pivot !== null && is_array($pivot)) {
            // Construire le nom de table complet si dbName est fourni
            $fullTableName = $dbName !== null ? "$dbName.$table" : $table;

            // Insertion sans tracking automatique
            $insertedId = $this->getRepository()->insert($fullTableName, $data, $types);

            // Résolution du pivot ID
            $pivotId = $this->resolvePivotId($pivot, $scope);
            $pivotColumn = isset($pivot['column']) && is_string($pivot['column']) ? $pivot['column'] : 'id';

            // Tracking manuel avec la colonne pivot et dbName
            $this->trackResource($table, $pivotId, $pivotColumn, $dbName);

            $this->logger->debug('✓ Enregistrement créé avec pivot: {table}#{id} (pivot: {column}={value})', [
                'table' => $dbName !== null ? "$dbName.$table" : $table,
                'id' => $insertedId,
                'column' => $pivotColumn,
                'value' => $pivotId
            ]);
        } else {
            // Tracking automatique par défaut (colonne id)
            $id = $this->insertAndTrack($table, $data, $types, 'id', $dbName);

            $this->logger->debug('✓ Enregistrement créé: {table}#{id}', [
                'table' => $dbName !== null ? "$dbName.$table" : $table,
                'id' => $id
            ]);
        }

        // Réinitialiser les variables temporaires de l'instruction précédente
        $this->loader->resetTemporaryVariables();
    }

    /**
     * Exécute une instruction de purge personnalisée
     *
     * @param array<string, mixed> $instruction
     */
    private function executePurgeInstruction(array $instruction, Scope $scope, int $index): void
    {
        if (!isset($instruction['table']) || !is_string($instruction['table'])) {
            throw new RuntimeException(sprintf(
                'Missing "table" key in purge instruction #%d',
                $index
            ));
        }

        $table = $instruction['table'];
        $dbName = isset($instruction['db']) && is_string($instruction['db']) ? $instruction['db'] : null;
        $fullTableName = $dbName !== null ? "$dbName.$table" : $table;
        $where = isset($instruction['where']) && is_array($instruction['where']) ? $instruction['where'] : [];

        // Résoudre les lookups dans les conditions WHERE
        foreach ($where as $column => $value) {
            if (is_array($value) && $this->isLookupReference($value)) {
                try {
                    /** @var array{table: string, where: array<string, mixed>, return: string, db?: string} $value */
                    $where[$column] = $this->resolveLookupReference($value, $scope);
                    $this->logger->debug('Lookup résolu dans purge: {column} = {value}', [
                        'column' => $column,
                        'value' => $where[$column]
                    ]);
                } catch (RuntimeException $e) {
                    // Si le lookup échoue (enregistrement pas encore créé), ignorer cette instruction de purge
                    $this->logger->warning('Lookup échoué dans purge (enregistrement non trouvé), '
                    . 'instruction ignorée: {table}', [
                        'table' => $fullTableName,
                        'error' => $e->getMessage()
                    ]);
                    return; // Ignorer cette instruction de purge
                }
            }
        }

        // Remplacer les placeholders dans les conditions WHERE
        $where = $this->loader->replacePlaceholders($where, $scope->toString());

        if (empty($where)) {
            $this->logger->warning('Purge instruction sans WHERE clause ignorée pour {table}', [
                'table' => $fullTableName
            ]);
            return;
        }

        $this->logger->debug('Purge personnalisé: {table}', [
            'table' => $fullTableName,
            'where' => $where
        ]);

        $deleted = $this->repository->delete($fullTableName, $where);

        $this->logger->debug('✓ Purge personnalisé: {count} lignes supprimées de {table}', [
            'count' => $deleted,
            'table' => $fullTableName
        ]);
    }

    /**
     * Vérifie si une valeur est une référence de lookup
     *
     * Format attendu :
     * [
     *   'table' => 'users',
     *   'where' => ['username' => 'admin'],
     *   'return' => 'id'
     * ]
     *
     * @param array<string, mixed> $value
     */
    private function isLookupReference(array $value): bool
    {
        return isset($value['table'])
            && isset($value['where'])
            && isset($value['return'])
            && is_string($value['table'])
            && is_array($value['where'])
            && is_string($value['return']);
    }

    /**
     * Résout une référence de lookup en interrogeant la base
     *
     * @param array{table: string, where: array<string, mixed>, return: string, db?: string} $reference
     * @return int|string
     * @throws RuntimeException Si aucun enregistrement n'est trouvé
     */
    private function resolveLookupReference(array $reference, Scope $scope): int|string
    {
        $table = $reference['table'];
        $dbName = isset($reference['db']) ? $reference['db'] : null;

        // Résoudre %connection% ou alias via le DatabaseNameResolver si fourni
        if ($dbName !== null) {
            try {
                $resolved = $this->dbNameResolver->resolve($dbName);
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf(
                    'Cannot resolve database name "%s" for lookup on table "%s": %s',
                    $dbName,
                    $table,
                    $e->getMessage()
                ));
            }

            // Si le resolver retourne une valeur, l'utiliser
            if ($resolved !== null) {
                $dbName = $resolved;
            }
        }

        $fullTableName = $dbName !== null ? sprintf('%s.%s', $dbName, $table) : $table;

        $where = $this->loader->replacePlaceholders($reference['where'], $scope->toString());
        $returnColumn = $reference['return'];

        // Construction de la requête SQL
        $whereClauses = [];
        $params = [];

        foreach ($where as $column => $value) {
            $whereClauses[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s LIMIT 1',
            $returnColumn,
            $fullTableName,
            implode(' AND ', $whereClauses)
        );

        $this->logger->debug('Résolution lookup: {table}.{column}', [
            'table' => $fullTableName,
            'column' => $returnColumn,
            'where' => $where
        ]);

        $result = $this->repository->executeQuery($sql, $params);

        if (empty($result)) {
            throw new RuntimeException(sprintf(
                'Lookup failed: No record found in table "%s" with conditions: %s',
                $fullTableName,
                json_encode($where, JSON_THROW_ON_ERROR)
            ));
        }

        $resolvedValue = $result[0][$returnColumn];

        $this->logger->debug('✓ Lookup résolu: {value}', [
            'value' => $resolvedValue
        ]);

        if (!is_int($resolvedValue) && !is_string($resolvedValue)) {
            throw new RuntimeException(sprintf(
                'Invalid lookup result type for column "%s": expected int or string, got %s',
                $returnColumn,
                get_debug_type($resolvedValue)
            ));
        }

        return $resolvedValue;
    }

    /**
     * Résout l'ID pour le pivot tracking
     *
     * @param array<string, mixed> $pivot
     * @return int|string
     */
    private function resolvePivotId(array $pivot, Scope $scope): int|string
    {
        if (!isset($pivot['id'])) {
            throw new RuntimeException('Missing "id" key in pivot configuration');
        }

        $pivotId = $pivot['id'];

        // Si c'est une référence lookup, on la résout
        if (is_array($pivotId) && $this->isLookupReference($pivotId)) {
            /** @var array{table: string, where: array<string, mixed>, return: string} $pivotId */
            return $this->resolveLookupReference($pivotId, $scope);
        }

        // Si c'est une valeur directe (int/string)
        if (is_int($pivotId)) {
            return $pivotId;
        }

        if (is_string($pivotId)) {
            // Utiliser le loader pour résoudre les placeholders (qui a accès aux variables temporaires)
            // On passe par replacePlaceholders avec un tableau temporaire pour garder le contexte
            $tempData = ['_pivot_id' => $pivotId];
            $resolved = $this->loader->replacePlaceholders($tempData, $scope->toString());
            $resolvedValue = $resolved['_pivot_id'];

            if (!is_int($resolvedValue) && !is_string($resolvedValue)) {
                throw new RuntimeException(sprintf(
                    'Invalid pivot id type after placeholder resolution: expected int or string, got %s',
                    get_debug_type($resolvedValue)
                ));
            }

            return $resolvedValue;
        }

        throw new RuntimeException('Invalid pivot id: must be a lookup reference or a direct value');
    }
}
