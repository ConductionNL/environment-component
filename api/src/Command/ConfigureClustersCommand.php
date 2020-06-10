<?php

// src/Command/ConfigureClustersCommand.php

namespace App\Command;

use App\Service\ClusterService;
use App\Service\DigitalOceanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigureClustersCommand extends Command
{
    private $clusterService;
    private $digitalOceanService;
    private $em;

    public function __construct(ClusterService $clusterService, EntityManagerInterface $em, DigitalOceanService $digitalOceanService)
    {
        $this->clusterService = $clusterService;
        $this->digitalOceanService = $digitalOceanService;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:clusters:configure')
        // the short description shown while running "php bin/console list"
        ->setDescription('Finds al the clusters the need to be configured and configures them')

        ->setHelp('This command wil loop trough the clusters that do not have a dateConfigured and see if they are ready by there provider, if so it wil then install the needed items');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $results = $this->em->getRepository('App\Entity\Cluster')->findConfigurable();

        $io->title('Checking '.count($results).' clusters');
        $io->progressStart(count($results));

        foreach ($results as $cluster) {
            $io->text("checking {$cluster->getName()}");
            $cluster->setStatus($this->digitalOceanService->getStatus($cluster));
            // check if the cluster is running
            if ($cluster->getStatus() == 'running') {
                $io->text("configuring {$cluster->getName()}");
                $cluster = $this->digitalOceanService->createKubeConfig($cluster);
                $this->clusterService->configureCluster($cluster);
                $now = new \DateTime();
                $cluster->setDateConfigured($now);
            } else {
                $io->text("{$cluster->getName()} not yet ready....");
            }

            $this->em->persist($cluster);
            $io->progressAdvance();
        }

        $this->em->flush();

        $io->progressFinish();
    }
}
