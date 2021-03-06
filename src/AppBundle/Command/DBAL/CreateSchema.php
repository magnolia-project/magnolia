<?php

declare(strict_types=1);

namespace AppBundle\Command\DBAL;

use Symfony\Component\Console\Command\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;
use Infrastructure\DBAL\FeedPersister;
use Infrastructure\DBAL\Tables;

class CreateSchema extends Command
{
    private $dbal;

    private $dbName;

    public function __construct(Connection $dbal, string $dbName)
    {
        $this->dbal = $dbal;
        $this->dbName = $dbName;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('magnolia:dbal:create-schema')
            ->setDescription('Create schema.')
            ->addOption('reset', 'r', InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('reset')) {
            $output->writeln('Dropping and recreating database.');

            $dbName = $this->dbal->getDatabase();

            $this->dbal->getSchemaManager()->dropAndCreateDatabase($dbName);
            $this->dbal->exec("USE {$dbName}");
        }

        $output->writeln('Creating schema.');

        $schema = new Schema();
        $feeds = $schema->createTable(Tables::FEEDS);
        $feeds->addColumn('id', 'string', [
            'length' => 36,
            'notnull' => true,
        ]);
        $feeds->addColumn('name', 'string', ['notnull' => true]);
        $feeds->addColumn('userId', 'string', [
            'notnull' => true,
            'length' => 36,
        ]);
        $feeds->addColumn('repositories', 'json_array', [
            'notnull' => true,
        ]);
        $feeds->setPrimaryKey(["id"]);

        $rawEvents = $schema->createTable(Tables::RAW_EVENTS);
        $rawEvents->addColumn('id', 'string', ['length' => 36, 'notnull' => true]);
        $rawEvents->addColumn('payload', 'json', ['notnull' => true]);
        $rawEvents->addColumn('date', 'datetime', ['notnull' => true]);
        $rawEvents->addColumn('repo_owner', 'string', ['notnull' => true]);
        $rawEvents->addColumn('repo_name', 'string', ['notnull' => true]);
        $rawEvents->setPrimaryKey(['id']);

        $events = $schema->createTable(Tables::EVENTS);
        $events->addColumn('id', 'string', ['length' => 36, 'notnull' => true]);
        $events->addColumn('type', 'string', ['notnull' => true]);
        $events->addColumn('date', 'datetime', ['notnull' => true]);
        $events->addColumn('repo_owner', 'string', ['notnull' => true]);
        $events->addColumn('repo_name', 'string', ['notnull' => true]);
        $events->setPrimaryKey(['id']);

        $queries = $schema->toSql($this->dbal->getDatabasePlatform());
        foreach($queries as $query) {
            $this->dbal->executeQuery($query);
        }

        $output->writeln('Schema created.');
    }
}
