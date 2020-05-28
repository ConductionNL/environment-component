<?php

// src/Command/UpdateComponentsCommand.php

namespace App\Command;

use App\Service\ExcelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoadExcelCommand extends Command
{
    private $excelService;
    private $em;

    public function __construct(ExcelService $excelService, EntityManagerInterface $em)
    {
        $this->excelService = $excelService;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:installations:load')
        // the short description shown while running "php bin/console list"
        ->setDescription('Add components that are not yet in the system.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil loop trough the excel file and add files that were not yet loaded.')
        ->setDescription('Update component list in DB');
        //->addOption('component', null, InputOption::VALUE_OPTIONAL, 'the component that you want to health check');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Installing or updating component properties');
        $this->excelService->load($this->em);
    }
}
