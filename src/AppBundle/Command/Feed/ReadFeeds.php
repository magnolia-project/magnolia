<?php

declare(strict_types=1);

namespace AppBundle\Command\Feed;

use Domain\Model\Feed;
use Symfony\Component\Console\Command\Command;
use UseCases\ReadFeeds as UseCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ReadFeeds extends Command
{
    /** @var UseCase\UseCase */
    private $useCase;

    /** @var string */
    private $defaultUserId;

    /**
     * @TODO: Remove default user id after real login process is implemented
     */
    public function __construct(UseCase\UseCase $useCase, string $defaultUserId)
    {
        $this->useCase = $useCase;
        $this->defaultUserId = $defaultUserId;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('magnolia:feeds:summary')
            ->addArgument('userId', InputArgument::OPTIONAL, '', $this->defaultUserId)
            ->setDescription('Read a summary of feeds for a user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $request = new UseCase\Request($input->getArgument('userId'));
        $response = $this->useCase->__invoke($request);

        if ($response->isSuccessful()) {
            $outputData = array_map(function(Feed $feed) {
                return [$feed->getId(), $feed->getName(), implode(', ', $feed->getRepositories())];
            }, $response->getFeeds());

            $table = new Table($output);
            $table->setHeaders(['id', 'name', 'repositories']);
            $table->setRows($outputData);
            $table->render();

            return 0;
        }

        $errorMessages = $response->getErrors();
        $formatter = $this->getHelper('formatter');
        $block = $formatter->formatBlock($errorMessages, 'error');

        $output->writeln($block);
        
        return 1;
    }
}
