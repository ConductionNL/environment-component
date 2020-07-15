<?php

namespace App\DataFixtures;

use App\Entity\OpenStackTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
        if (strpos($this->params->get('app_domain'), 'conduction.nl') == false && $this->params->get('app_domain')!='conduction.nl') {
            return false;
        }

        $template = new OpenStackTemplate();
        $template->setName('fuga.small');
        $template->setImage('ac6c15cc-9073-4537-98d9-00f4ccfefa25');
        $template->setMasterFlavour('t3.small');
        $template->setNodeCount(2);
        $template->setVolumeSize(15);
        $template->setVersionTag('v1.13.10');

        $manager->persist($template);
        $manager->flush();
    }
}
