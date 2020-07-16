<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use App\Service\HealthService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
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
        $componentId = $input->getOption('cluster');

        if ($componentId) {
            $clusters = $this->em->getRepository('App\Entity\Cluster')->findBy();
        } else {
            $clusters = $this->em->getRepository('App\Entity\Cluster')->findAll();
        }

        if (!$clusters || count($clusters) < 1) {
            throw new InvalidOptionException(sprintf('No installable clusters could be found'));
        }

        $io->title('Starting health checks');

        $installations = [];

        foreach ($clusters as $cluster) {
            $installations = array_merge($installations, $cluster->getInstallations()->toArray());
        }

        $io->text('Found '.count($clusters).' clusters to check');
        $io->text('Found '.count($installations).' installations to check');

        if (count($installations) > 0) {
            $io->progressStart(count($installations));
        }

        $results = [];
        foreach ($installations as $installation) {
            $health = $this->healthService->check($installation);
            $results[] = [$health->getDomain()->getName(), $health->getInstallation()->getEnvironment()->getName(), $health->getInstallation()->getName(), $health->getEndpoint(), $health->getStatus()];

            $io->progressAdvance();
        }

        $io->text('All done');

        $io->table(
            ['Cluster', 'Enviroment', 'Installation', 'Endpoint', 'Status'],
            $results
        );
    }
}
