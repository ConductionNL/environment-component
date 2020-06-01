<?php

// src/Command/ConfigureK8ClusterCommand.php

namespace App\Command;

use App\Service\ClusterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigureK8ClusterCommand  extends Command
{
    private $installService;
    private $em;

    public function __construct(ClusterService $clusterService, EntityManagerInterface $em)
    {
        $this->clusterService = $clusterService;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:k8cluster:configure')
        // the short description shown while running "php bin/console list"
        ->setDescription('Configure a given cluster')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil install all the basic nececities on a cluster')
        ->addArgument('cluster', null, InputArgument::REQUIRED, 'the uudid of the cluster that you want to configure');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $cluster = $this->em->getRepository('App\Entity\Cluster')->find( $input->getArgument('cluster'));
        $io->title('Deleting '.$cluster->getName().' ('.$cluster->getId().')');
        $this->clusterService->delete($cluster);

    }
}
