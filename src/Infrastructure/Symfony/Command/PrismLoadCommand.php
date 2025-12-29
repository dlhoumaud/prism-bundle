<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Symfony\Command;

use Prism\Application\UseCase\LoadPrism;
use Prism\Domain\Contract\PrismRegistryInterface;
use Prism\Domain\ValueObject\Scope;
use Prism\Domain\ValueObject\PrismName;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command Symfony : Charger un scénario fonctionnel
 */
#[AsCommand(
    name: 'app:prism:load',
    description: 'Charge un scénario de données fonctionnelles'
)]
final class PrismLoadCommand extends Command
{
    public function __construct(
        private readonly LoadPrism $loadPrism,
        private readonly PrismRegistryInterface $prismRegistry,
        private readonly bool $enabled = true
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('prism', InputArgument::REQUIRED, 'Nom du scénario à charger')
            ->addOption('scope', 's', InputOption::VALUE_REQUIRED, 'Scope du scénario', 'default')
            ->setHelp(<<<'HELP'
Charge un scénario de données fonctionnelles dans la base de données.

<info>Exemple d'utilisation :</info>
  php bin/console app:prism:load test_users --scope=dev_team_alpha
  
Le scénario purge automatiquement les données existantes du même scope avant chargement.

<comment>Bonnes pratiques :</comment>
  - Utilisez des scopes préfixés par 'test_', 'dev_' ou 'prism_'
  - Un scope par développeur ou équipe pour éviter les collisions
  - Purgez régulièrement les scopes inutilisés
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            /** @var string $prismArg */
            $prismArg = $input->getArgument('prism');
            /** @var string $scopeOpt */
            $scopeOpt = $input->getOption('scope');

            $prismName = PrismName::fromString($prismArg);
            $scope = Scope::fromString($scopeOpt);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        }

        $prism = $this->prismRegistry->get($prismName);

        if ($prism === null) {
            $io->error(sprintf('Scénario "%s" introuvable', $prismName->toString()));
            $io->note('Utilisez la commande "app:prism:list" pour voir les scénarios disponibles');
            return Command::FAILURE;
        }

        $io->title(sprintf(
            'Chargement du scénario "%s"',
            $prismName->toString()
        ));

        $io->info(sprintf('Scope: %s', $scope->toString()));

        try {
            $startTime = microtime(true);

            $this->loadPrism->execute($prism, $scope);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $io->success([
                'Scénario chargé avec succès',
                sprintf('Temps d\'exécution: %s ms', $executionTime)
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error([
                'Erreur lors du chargement du scénario',
                $e->getMessage()
            ]);

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), null, 'fg=white;bg=red', ' ', true);
            }

            return Command::FAILURE;
        }
    }
}
