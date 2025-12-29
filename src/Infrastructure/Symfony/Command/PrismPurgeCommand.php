<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Symfony\Command;

use Prism\Application\UseCase\PurgePrism;
use Prism\Domain\Contract\PrismRegistryInterface;
use Prism\Domain\Contract\PrismResourceTrackerInterface;
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
 * Command Symfony : Purger un scénario fonctionnel
 */
#[AsCommand(
    name: 'app:prism:purge',
    description: 'Purge les données d\'un scénario fonctionnel'
)]
final class PrismPurgeCommand extends Command
{
    public function __construct(
        private readonly PurgePrism $purgePrism,
        private readonly PrismRegistryInterface $prismRegistry,
        private readonly PrismResourceTrackerInterface $tracker,
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
            ->addArgument('prism', InputArgument::OPTIONAL, 'Nom du scénario à purger (optionnel si --all)')
            ->addOption('scope', 's', InputOption::VALUE_REQUIRED, 'Scope du scénario', 'default')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Purger tous les scénarios du scope')
            ->setHelp(<<<'HELP'
Purge les données d'un scénario fonctionnel pour un scope donné.

<info>Exemples d'utilisation :</info>
  # Purger un scénario spécifique
  php bin/console app:prism:purge test_users --scope=dev_team_alpha
  
  # Purger tous les scénarios d'un scope
  php bin/console app:prism:purge --scope=dev_team_alpha --all

<comment>Attention :</comment>
  Cette commande supprime définitivement les données. Assurez-vous d'utiliser
  le bon scope pour ne pas supprimer des données d'autres développeurs.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            /** @var string $scopeOpt */
            $scopeOpt = $input->getOption('scope');
            $scope = Scope::fromString($scopeOpt);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        }

        $purgeAll = $input->getOption('all');
        $prismArg = $input->getArgument('prism');

        if (!$purgeAll && !$prismArg) {
            $io->error('Vous devez spécifier un scénario ou utiliser l\'option --all');
            return Command::INVALID;
        }

        if ($purgeAll) {
            return $this->purgeAllPrisms($io, $scope);
        }

        try {
            /** @var string $prismArg */
            $prismName = PrismName::fromString($prismArg);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        }

        $prism = $this->prismRegistry->get($prismName);

        if ($prism === null) {
            $io->error(sprintf('Scénario "%s" introuvable', $prismName->toString()));
            return Command::FAILURE;
        }

        $io->title(sprintf('Purge du scénario "%s"', $prismName->toString()));
        $io->info(sprintf('Scope: %s', $scope->toString()));

        try {
            $this->purgePrism->execute($prism, $scope);

            $io->success('Scénario purgé avec succès');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error([
                'Erreur lors de la purge du scénario',
                $e->getMessage()
            ]);

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), null, 'fg=white;bg=red', ' ', true);
            }

            return Command::FAILURE;
        }
    }

    private function purgeAllPrisms(SymfonyStyle $io, Scope $scope): int
    {
        $io->title(sprintf('Purge de tous les scénarios du scope "%s"', $scope->toString()));

        if (!$io->confirm('Êtes-vous sûr de vouloir purger TOUS les scénarios de ce scope ?', false)) {
            $io->note('Opération annulée');
            return Command::SUCCESS;
        }

        try {
            $prisms = $this->prismRegistry->all();
            $purgedCount = 0;

            foreach ($prisms as $prism) {
                try {
                    $this->purgePrism->execute($prism, $scope);
                    $purgedCount++;
                    $io->writeln(sprintf('✓ %s purgé', $prism->getName()->toString()));
                } catch (\Throwable $e) {
                    $io->warning(sprintf(
                        'Impossible de purger %s: %s',
                        $prism->getName()->toString(),
                        $e->getMessage()
                    ));
                }
            }

            $io->success(sprintf('%d scénario(s) purgé(s) avec succès', $purgedCount));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error([
                'Erreur lors de la purge globale',
                $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
}
