<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use App\Service\HealthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCommand extends Command
{
    private $em;
    private $queueService;

    public function __construct(EntityManagerInterface $em, HealthService $healthService)
    {
        $this->em = $em;
        $this->healthService = $healthService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:health:check')
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command will perform a health check on all or a single installation')
            // the short description shown while running "php bin/console list"
        ->setDescription('Perform health check on cluster')
        ->addOption('installation', null, InputOption::VALUE_OPTIONAL, 'the installation that you want to health check');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Health checks for commonground installations');
        $io->text([
            'This command will',
            '- Make a API based JSON health check on all known installations',
            '- Update the cluster information accordingly',
            '- Update the enviroment information accordingly',
            '- Report its results',
        ]);

        //$io->section('Removing old health checks');
        //$this->em->getRepository('App\Entity\HealthLog')->removeOld();

        /** @var string $version */
        $installationId = $input->getOption('installation');

        if ($installationId) {
            $clusters = $this->em->getRepository('App\Entity\Cluster')->findBy();
        } else {
            $installations = $this->em->getRepository('App\Entity\Installation')->findAll();
        }

        if (!$installations || count($installations) < 1) {
            $io->error('Found no installations to check');

            return;
        }

        $io->section('Starting health checks');

        $io->text('Found '.count($installations).' installations to check');

        $io->progressStart(count($installations));

        $results = [];
        $clustersHealth = [];
        $clustersInstallations = [];
        $environmentsHealth = [];

        foreach ($installations as $installation) {
            $health = $this->healthService->check($installation);
            $results[] = [$health->getInstallation()->getEnvironment()->getCluster()->getName(), $health->getDomain()->getName(), $health->getInstallation()->getEnvironment()->getName(), $health->getInstallation()->getName(), $health->getEndpoint(), $health->getStatus()];

            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->section('Updating enviroments');
        $enviroments = $this->em->getRepository('App\Entity\Enviroment')->findAll();
        $io->text('Found '.count($enviroments).' enviroments to update');
        $io->progressStart(count($enviroments));

        foreach ($enviroments as $enviroment) {
            $enviroment->setHealth(count($enviroment->getHealthyInstallations()));
            $this->em->persist($enviroment);

            $io->progressAdvance();
        }
        $io->progressFinish();
        $this->em->flush();

        $io->section('Updating clusters health');
        $clusters = $this->em->getRepository('App\Entity\Cluster')->findAll();
        $io->text('Found '.count($clusters).' clusters to update');
        $io->progressStart(count($clusters));

        // Let registr the statistical results to there proper entities
        foreach ($clusters as $cluster) {

            $installations = 0;
            $health = 0;
            foreach($cluster->getEnviroments() as $enviroment){
                $installations = $installations + count( $enviroment->getInstallations());
                $health = $health + $enviroment->getHealth();
            }
            $cluster->setInstallations($installations);
            $cluster->setHealth($health);
            $this->em->persist($cluster);

            $io->progressAdvance();
        }
        $io->progressFinish();
        $this->em->flush();

        $io->success('All done');

        $io->section('results');
        $io->table(
            ['Cluster', 'Domain', 'Enviroment', 'Installation', 'Endpoint', 'Status'],
            $results
        );
    }
}
