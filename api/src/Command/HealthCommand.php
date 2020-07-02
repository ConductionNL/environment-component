<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use App\Service\HealthService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Common\Collections\ArrayCollection;

class HealthCommand extends Command
{
    private $em;    private $healthService;

    public function __construct(HealthService $healthService)
    {
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
        ->setHelp('This command will perform a health check on all or a single cluster')
        ->setDescription('Perform health check on cluster')
        ->addOption('cluster', null, InputOption::VALUE_OPTIONAL, 'the cluster that you want to health check');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $version */
        $componentId = $input->getOption('component');

        if($componentId){
            $clusters = $this->em->getRepository('App\Entity\Clusters')->findBy();
        }
        else{
            $clusters = $this->em->getRepository('App\Entity\Clusters')->getAll();

        }

        if (!$clusters || count($clusters) < 1 ) {
        	throw new InvalidOptionException(sprintf('No installable clusters could be found'));
        }

        $io->title('Starting health checks');

        $installations = new ArrayCollection();
        foreach($cluster as $clusters){
            $installations = new ArrayCollection(
                array_merge($installations->toArray(), $cluster->getInstallations())
            );
        }

        $io->text('Found '.count($clusters).' clusters to check');
        $io->text('Found '.count($installations).' installations to check');

        if (count($installations) > 0) {
            $io->progressStart(count($installations));
        }

        $results = [];
        foreach ($installations as $installation) {
            $health = $this->healthService->check($installation);
            $results[] = [$health->getDomain()->getName(), $health->getInstallation()->getEnviroment()->getName(), $health->getInstallation()->getName(), $health->getEndpoint(), $health->getStatus()];

            $io->progressAdvance();
        }

        $io->text('All done');

        $io->table(
            ['Cluster', 'Enviroment','Installation','Endpoint','Status'],
            $results
        );
    }
}
