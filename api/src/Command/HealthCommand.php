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

            // Lets create some statistical data
            /*
            if(!array_key_exists( $health->getInstallation()->getEnvironment()->getCluster(), $clusters)){
                $clusters[$health->getInstallation()->getEnvironment()->getCluster()] = ['health' => 0, 'installations' => 0 ];
            }

            if(!array_key_exists( $health->getInstallation()->getEnvironment(), $environment)){
                $environments[$health->getInstallation()->getEnvironment()] = ['health' => 0, 'installations' => 0 ];
            }

            if($health->getStatus() == "OK" || $health->getStatus() == "Found"){
                $clusters[$health->getInstallation()->getEnvironment()->getCluster()]['health'] = $clusters[$health->getInstallation()->getEnvironment()->getCluster()]['health'] + 1;
                $environments[$health->getInstallation()->getEnvironment()]['health'] =  $environments[$health->getInstallation()->getEnvironment()]['health'] + 1;
            }

            $clusters[$health->getInstallation()->getEnvironment()->getCluster()]['installations'] = $clusters[$health->getInstallation()->getEnvironment()->getCluster()]['installations'] + 1;
            $environments[$health->getInstallation()->getEnvironment()]['installations'] = $environments[$health->getInstallation()->getEnvironment()]['installations'] +1;
            */

            $clustersHealth[$health->getInstallation()->getEnvironment()->getCluster()->getId()] = 1;
            $clustersInstallations[$health->getInstallation()->getEnvironment()->getCluster()->getId()] = 1;
            $environmentsHealth[$health->getInstallation()->getEnvironment()->getId()] = 1;

            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->section('Updating clusters health');
        $io->text('Found '.count($clustersHealth).' clusters to update');
        $io->progressStart(count($clustersHealth));

        // Let registr the statistical results to there proper entities
        foreach ($clustersHealth as $key => $value) {

            /*
            $key->setHealth($value['health']);
            $key->setInstallations($value['installations']);

            $io->text('Updating cluster:'.$key->getId());
            $this->em->persist($key);
            */
            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->section('Updating clusters installations');
        $io->text('Found '.count($clustersInstallations).' clusters to update');
        $io->progressStart(count($clustersInstallations));

        // Let registr the statistical results to there proper entities
        foreach ($clustersInstallations as $key => $value) {

            /*
            $key->setHealth($value['health']);
            $key->setInstallations($value['installations']);

            $io->text('Updating cluster:'.$key->getId());
            $this->em->persist($key);
            */
            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->section('Updating enviroments');
        $io->text('Found '.count($environmentsHealth).' enviroments to update');
        $io->progressStart(count($environmentsHealth));

        foreach ($environmentsHealth as $key => $value) {

            /*
            $key->setHealth($value['health']);

            $io->text('Updating $environments:'.$key->getId());

            $this->em->persist($key);
            */
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
