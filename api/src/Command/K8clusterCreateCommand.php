<?php

namespace App\Command;

use App\Entity\Cluster;
use App\Service\DigitalOceanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class K8clusterCreateCommand extends Command
{
    private $doService;
    private $em;

    public function __construct(DigitalOceanService $clusterService, EntityManagerInterface $em)
    {
        $this->doService = $clusterService;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:k8clusters:create')
            // the short description shown while running "php bin/console list"
            ->setDescription('Create a given cluster')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command wil create a complete cluster if it does not exist yet');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Looking for clusters');
        $clusters = $this->em->getRepository('App\Entity\Cluster')->findBy(['status'=>'requested']);
        foreach ($clusters as $cluster) {
            if ($cluster instanceof Cluster) {
                $io->title('Creating '.$cluster->getName().' ('.$cluster->getId().')');
                $this->doService->createKubernetesCluster($cluster);
            }
        }
    }
}
