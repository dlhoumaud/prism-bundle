<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Symfony\Command;

use Prism\Application\UseCase\ListPrisms;
use Prism\Application\Prism\YamlPrism;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command Symfony : Lister les scénarios disponibles
 */
#[AsCommand(
    name: 'app:prism:list',
    description: 'Liste tous les scénarios fonctionnels disponibles'
)]
final class PrismListCommand extends Command
{
    public function __construct(
        private readonly ListPrisms $listPrisms,
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
        $this->setHelp(<<<'HELP'
Liste tous les scénarios fonctionnels disponibles dans l'application.

<info>Exemple d'utilisation :</info>
  php bin/console app:prism:list

Chaque scénario peut ensuite être chargé avec :
  php bin/console app:prism:load <nom_du_prism> --scope=<votre_scope>
HELP
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $prisms = $this->listPrisms->execute();

        if (empty($prisms)) {
            $io->warning('Aucun scénario disponible');
            $io->note([
                'Pour créer un scénario, créez une classe qui étend AbstractPrism',
                'dans src/Prism/Infrastructure/Prism/'
            ]);
            return Command::SUCCESS;
        }

        $io->title('Scénarios fonctionnels disponibles');

        $rows = [];
        foreach ($prisms as $prism) {
            $rows[] = [
                $prism->getName()->toString(),
                $prism instanceof YamlPrism ? 'YAML' : get_class($prism),
            ];
        }

        $io->table(
            ['Nom du scénario', 'Type'],
            $rows
        );

        $io->info(sprintf('%d scénario(s) disponible(s)', count($prisms)));

        $io->section('Utilisation');
        $io->writeln([
            'Pour charger un scénario :',
            '  <info>php bin/console app:prism:load <prism_name> --scope=<your_scope></info>',
            '',
            'Pour purger un scénario :',
            '  <info>php bin/console app:prism:purge <prism_name> --scope=<your_scope></info>',
        ]);

        return Command::SUCCESS;
    }
}
