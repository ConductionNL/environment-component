<?php

// src/Command/UpdateComponentsCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;


use App\Service\InstallService;

class UpdateComponentsCommand extends Command
{

    private $installService;
    private $em;

    public function __construct(InstallService  $installService, EntityManagerInterface $em)
    {
        $this->installService = $installService;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:installations:install')
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

        $results = $this->em->getRepository('App\Entity\Installation')->findInstallable();

        $io->title('Installing or updating '.count($results).' installations');
        $io->progressStart(count($results));

        foreach($results as $result){
            $io->progressAdvance();

            $this->installService->update($result);
            //$io->warning('Lorem ipsum dolor sit amet');
            //$io->success('Lorem ipsum dolor sit amet');

        }

        $io->progressFinish();

    }
}
