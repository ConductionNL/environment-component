<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use App\Service\HealthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
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
        // the short description shown while running "php bin/console list"
        ->setDescription('Creates a new helm chart.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command will perform a health check on all or a single installation')
        ->setDescription('Perform health check on cluster')
        ->addOption('installation', null, InputOption::VALUE_OPTIONAL, 'the installation that you want to health check');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $version */
        $installationId = $input->getOption('installation');

        if ($installationId) {
            $clusters = $this->em->getRepository('App\Entity\Cluster')->findBy();
        } else {
            $installations = $this->em->getRepository('App\Entity\Installation')->findAll();
        }

        if (!$installations || count($installations) < 1) {
            throw new InvalidOptionException(sprintf('No installations could be found'));
        }

        $io->title('Starting health checks');


        $io->text('Found '.count($installations).' installations to check');

        $io->progressStart(count($installations));

        $results = [];
        $clusters =[];
        $environment =[];

        foreach ($installations as $installation) {
            $health = $this->healthService->check($installation);
            $results[] = [$health->getDomain()->getName(), $health->getInstallation()->getEnvironment()->getName(), $health->getInstallation()->getName(), $health->getEndpoint(), $health->getStatus()];


            // Lets create some statistical data
            if(!array_key_exists( $health->getInstallation()->getEnvironment()->getCluster(), $clusters)){
                $clusters[$health->getInstallation()->getEnvironment()->getCluster()] = ['health' => 0, 'installations' => 0 ];
            }

            if(!array_key_exists( $health->getInstallation()->getEnvironment(), $environment)){
                $environment[$health->getInstallation()->getEnvironment()] = ['health' => 0, 'installations' => 0 ];
            }

            if($health->getStatus() == "OK"){
                $clusters[$health->getInstallation()->getEnvironment()->getCluster()]['health'] ++;
                $environment[$health->getInstallation()->getEnvironment()]['health']  ++;
            }

            $clusters[$health->getInstallation()->getEnvironment()->getCluster()]['installations'] ++;
            $environment[$health->getInstallation()->getEnvironment()]['installations']  ++;

            $io->progressAdvance();
        }

        // Let registr the statistical results to there proper entities
        foreach ($clusters as $key => $value){

            $key->setHealth($value['health']);
            $key->setInstallations($value['installations']);

            $this->em->persist($key);
        }


        foreach ($clusters as $key => $value){

            $key->setHealth($value['health']);

            $this->em->persist($key);
        }

        $this->em->flush();

        $io->text('All done');

        $io->table(
            ['Domain', 'Enviroment', 'Installation', 'Endpoint', 'Status'],
            $results
        );
    }
}
