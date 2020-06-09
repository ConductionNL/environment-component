<?php

// src/Command/UpdateK8ClusterCommand.php

namespace App\Command;

use App\Service\ClusterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateK8ClusterCommand extends Command
{
    private $clusterService;
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
        ->setName('app:k8cluster:update')
        // the short description shown while running "php bin/console list"
        ->setDescription('create a given k8 clusters')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil loop trough the components provided by excel and create any that do not yet exisit')
        ->addArgument('cluster', null, InputArgument::REQUIRED, 'the uudid of the cluster that you want to create');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $cluster = $this->em->getRepository('App\Entity\Cluster')->find($input->getArgument('cluster'));

        if ($cluster->getDateInstalled() != null) {
            $io->title('Updating  K8 cluster'.$cluster->getName().' ('.$cluster->getId().')');
            $this->clusterService->update($cluster, 'prod');
        } else {
            $io->title('Installing K8 cluster'.$cluster->getName().' ('.$cluster->getId().')');
            $this->clusterService->install($cluster, 'prod');
        }
    }
}
