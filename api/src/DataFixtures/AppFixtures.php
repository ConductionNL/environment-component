<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

// The Entities
use App\Entity\Cluster;
use App\Entity\Enviroment;
use App\Entity\Domain;

class AppFixtures extends Fixture
{
    private $params;
    private $encoder;

    public function __construct(ParameterBagInterface $params, UserPasswordEncoderInterface $encoder)
    {
        $this->params = $params;
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        if (strpos($this->params->get('app_domain'), "conduction.nl") == false) {
            return false;
        }

        $domain = new Domain();
        $domain->setName('conduction.nl');
        $domain->setDescription('The core conduction domain');
        $domain->setLocation('conduction.nl');
        $manager->persist($domain);

        $cluster = new Cluster();
        $cluster->setName('conduction.nl');
        $cluster->setDescription('The core conduction cluter');
        $manager->persist($cluster);

        $enviroment = new Enviroment();
        $enviroment->setName('prod');
        $enviroment->setDescription('The production enviroment');
        $enviroment->setDebug('false');
        $enviroment->setCluster($cluster);
        $enviroment->setDomain($domain);
        $manager->persist($enviroment);

        $enviroment = new Enviroment();
        $enviroment->setName('stag');
        $enviroment->setDescription('The staging enviroment');
        $enviroment->setDebug('false');
        $enviroment->setCluster($cluster);
        $enviroment->setDomain($domain);
        $manager->persist($enviroment);

        $enviroment = new Enviroment();
        $enviroment->setName('dev');
        $enviroment->setDescription('The development enviroment');
        $enviroment->setDebug('false');
        $enviroment->setCluster($cluster);
        $enviroment->setDomain($domain);
        $manager->persist($enviroment);

        $manager->flush();
    }
}
