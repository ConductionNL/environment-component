<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:health:check')
        // the short description shown while running "php bin/console list"
        ->setDescription('Creates a new helm chart.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command allows you to create a new hel chart from the helm template')
        ->setAliases(['app:helm:export'])
        ->setDescription('Dump the OpenAPI documentation')
        ->addOption('component', null, InputOption::VALUE_OPTIONAL, 'the component that you want to health check')
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $version */
        $componentId = $input->getOption('component');

        // get component

        if (!$component) {
        	throw new InvalidOptionException(sprintf('A component with given id could not be found ("%s" given).', $componentId));
        }

        // do some magic

    }
}
