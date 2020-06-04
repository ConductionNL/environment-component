<?php

// src/Command/DeleteComponentCommand.php

namespace App\Command;

use App\Service\InstallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteComponentCommand extends Command
{
    private $installService;
    private $em;

    public function __construct(InstallService $installService, EntityManagerInterface $em)
    {
        $this->installService = $installService;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('app:component:delete')
        // the short description shown while running "php bin/console list"
        ->setDescription('Delete a given component')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil delete a component from kubernetes')
        ->addArgument('component', null, InputArgument::REQUIRED, 'the uudid of the component that you want to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $installation = $this->em->getRepository('App\Entity\Installation')->find( $input->getArgument('component'));
        $io->title('Deleting '.$installation->getName().' ('.$installation->getId().')');
        if($installation->getDateInstalled()){
            $this->installService->delete($installation);
        }

    }
}
