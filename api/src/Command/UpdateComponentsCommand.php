<?php

// src/Command/UpdateComponentsCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Service\InstallService;

class UpdateComponentsCommand extends Command
{

    private $installService;

    public function __construct(InstallService  $installService)
    {
        $this->installService = $installService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:components:update')
        // the short description shown while running "php bin/console list"
        ->setDescription('Updates components in the database from a given excel.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil loop trough the components provided by excel and create any that do not yet exisit')
        ->setDescription('Update component list in DB');
        //->addOption('component', null, InputOption::VALUE_OPTIONAL, 'the component that you want to health check');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->installService->updateComponents(false);

    }
}
