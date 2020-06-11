<?php

// src/Command/UpdateComponentsCommand.php

namespace App\Command;

use App\Entity\Installation;
use App\Service\InstallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class InstallInstalationsCommand extends Command
{
    private $installService;
    private $em;

    public function __construct(InstallService $installService, EntityManagerInterface $em)
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
        $processes = [];
        foreach ($results as $key=>$result) {
            $io->progressAdvance();
            $io->text("Installing {$result->getComponent()->getName()} on {$result->getDomain()->getCluster()->getName()}");
            if($result instanceof Installation && $result->getDateInstalled() == null || $result->getDateInstalled()->diff($result->getDateModified())->d != 0){
                $processes[$key] = new Process(['bin/console','app:component:update', "{$result->getId()}"]);
                $processes[$key]->start();
            }

            //$io->warning('Lorem ipsum dolor sit amet');
            //$io->success('Lorem ipsum dolor sit amet');
        }
        $errors = [];
        foreach($processes as $key=>$process){
            $process->wait();
            if(!$process->isSuccessful()){
                $errors[$key] =  new ProcessFailedException($process);
            }
        }
        foreach($errors as $error){
            echo $error->getMessage();
        }

        $io->progressFinish();
    }
}
