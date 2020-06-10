<?php

// src/Command/UpdateComponentCommand.php

namespace App\Command;

use App\Service\InstallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateComponentCommand extends Command
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
        ->setName('app:component:update')
        // the short description shown while running "php bin/console list"
        ->setDescription('Install a given component')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command wil loop trough the components provided by excel and create any that do not yet exisit')
        ->addArgument('component', null, InputArgument::REQUIRED, 'the uudid of the component that you want to install');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $installation = $this->em->getRepository('App\Entity\Installation')->find($input->getArgument('component'));

        if ($installation->getDateInstalled() != null) {
            $io->title('Updating '.$installation->getName().' ('.$installation->getId().')');
            $this->installService->update($installation);
        } else {
            $io->title('Installing '.$installation->getName().' ('.$installation->getId().')');
            $this->installService->install($installation);
        }
    }
}
