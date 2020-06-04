<?php

// src/Command/UpdateDatabaseClusterCommand.php

namespace App\Command;

use App\Service\DatabaseClusterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateDatabaseClusterCommand extends Command
{
    private $databaseClusterService;
    private $em;

    public function __construct(DatabaseClusterService $databaseClusterService, EntityManagerInterface $em)
    {
        $this->databaseClusterService = $databaseClusterService;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:dbcluster:update')
        // the short description shown while running "php bin/console list"
        ->setDescription('Creates a given Database cluster on a provider from a given entity')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil look up a database cluster by the given uuid id and then try to create it on its given provider')
        ->addArgument('cluster', null, InputArgument::REQUIRED, 'the uudid of the cluster that you want to create');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $databaseCluster = $this->em->getRepository('App\Entity\Cluster')->find( $input->getArgument('cluster'));

        if($databaseCluster->getDateInstalled() != null){
            $io->title('Updating  database cluster'.$databaseCluster->getName().' ('.$databaseCluster->getId().')');
            $this->clusterService->update($databaseCluster);
        }
        else{
            $io->title('Installing database cluster'.$databaseCluster->getName().' ('.$databaseCluster->getId().')');
            $this->clusterService->install($databaseCluster);
        }
    }
}
