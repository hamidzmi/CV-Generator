<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Presentation\Console;

use App\Modules\Indexing\Infrastructure\Weaviate\WeaviateSchemaConfigurator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:indexing:sync-schema',
    description: 'Ensure the Weaviate schema for work log entries exists.',
)]
final class SyncSchemaCommand extends Command
{
    public function __construct(private readonly WeaviateSchemaConfigurator $configurator)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $created = $this->configurator->ensureSchema();

        if ($created) {
            $io->success('Weaviate class created successfully.');
        } else {
            $io->info('Weaviate class already present.');
        }

        return Command::SUCCESS;
    }
}
