<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

// The Entities
use App\Entity\Cluster;
use App\Entity\Environment;
use App\Entity\Domain;
use App\Entity\Component;
use App\Entity\Installation;
use PhpOffice\PhpSpreadsheet;

class ConductionFixtures extends Fixture
{
    private $params;
    private $encoder;

    public function __construct(ParameterBagInterface $params, UserPasswordEncoderInterface $encoder)
    {
        $this->params = $params;
        $this->encoder = $encoder;
    }
    public function createReader(): PhpSpreadsheet\Reader\Xlsx
    {
        $reader = new PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);

        return $reader;
    }

    public function loadXlsx(string $filename): PhpSpreadsheet\Spreadsheet
    {
        $reader = $this->createReader();

        try {
            return $reader->load($filename);
        } catch (PhpSpreadsheet\Reader\Exception $e) {
        }

        return null;
    }
    public function loadComponentsFromSpreadsheet(
        Worksheet $components,
        Domain $domain,
        ArrayCollection $environments,
        ObjectManager $manager
    ){
        $j=0;
        foreach($components->toArray() as $componentArray){
            if($j >= $components->getHighestRow()){
                break;
            }
            // Component Lists
            $component = new Component();
            $component->setCode($componentArray[0]);
            $component->setName($componentArray[1]);
            $component->setDescription($componentArray[2]);
            $component->setGithubRepository($componentArray[3]);
            $component->setHelmRepository($componentArray[4]);
            $manager->persist($component);

            // Setup an installation for above component

            foreach($environments as $environment){
                $installation = new Installation();
                $installation->setComponent($component);
                $installation->setDomain($domain);
                $installation->setEnvironment($environment);
                $installation->setAuthorization($environment->getAuthorization());
                $installation->setName($component->getName());
                $installation->setDescription($component->getDescription());
                $installation->setHelmVersion('v2.12.3');
                $manager->persist($installation);
            }

            $j++;
        }
    }
    public function loadEnvironmentsFromSpreadsheet(
        Worksheet $environments,
        Cluster $cluster,
        ObjectManager $manager
    ){
        $envs = new ArrayCollection();
        $i = 0;
        foreach($environments->toArray() as $environmentArray) {
            if ($i >= $environments->getHighestRow()) {
                break;
            }
            $env = new Environment();
            $env->setName($environmentArray[0]);
            $env->setDescription($environmentArray[1]);
            $env->setDebug($environmentArray[2]);
            $env->setAuthorization($environmentArray[3]);
            $env->setCluster($cluster);
            $manager->persist($env);
            $envs->add($env);
        }
        return $envs;
    }
    public function loadClustersFromSpreadsheet(
        Worksheet $clusters,
        Worksheet $components,
        Worksheet $environments,
        ObjectManager $manager
    ){
        $i = 0;
        foreach($clusters->toArray() as $clusterArray){
            if($i >= $clusters->getHighestRow()){
                break;
            }
            $cluster = new Cluster();
            $cluster->setName($clusterArray[0]);
            $cluster->setDescription($clusterArray[1]);
            $manager->persist($cluster);

            $domain = new Domain();
            $domain->setName($clusterArray[0]);
            $domain->setDescription($clusterArray[2]);
            $domain->setLocation($clusterArray[3]);
            $domain->setCluster($cluster);
            $manager->persist($domain);

            $envs = $this->loadEnvironmentsFromSpreadsheet($environments, $cluster, $manager);

            $this->loadComponentsFromSpreadsheet($components, $domain, $envs, $manager);
            $manager->flush();
            $i++;
        }
    }
    public function loadFromSpreadsheet(string $filename, ObjectManager $manager){
        $spreadSheet = $this->loadXlsx($filename);

        $clusters = $spreadSheet->getSheetByName('clusters');
        $components = $spreadSheet->getSheetByName('components');
        $environments = $spreadSheet->getSheetByName('environments');

        $this->loadClustersFromSpreadsheet($clusters, $components, $environments, $manager);


    }

    public function load(ObjectManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        if (strpos($this->params->get('app_domain'), "conduction.nl") == false) {
            //return false;
        }
        $this->loadFromSpreadsheet(dirname(__FILE__).'/resources/components.xlsx', $manager);


//        $cluster = new Cluster();
//        $cluster->setName('conduction.nl');
//        $cluster->setDescription('The core conduction cluster');
//        $manager->persist($cluster);
//
//        $domain = new Domain();
//        $domain->setName('conduction.nl');
//        $domain->setDescription('The core conduction domain');
//        $domain->setLocation('conduction.nl');
//        $domain->setCluster($cluster);
//        $manager->persist($domain);
//
//        $prod = new Environment();
//        $prod->setName('prod');
//        $prod->setDescription('The production enviroment');
//        $prod->setDebug(0);
//        $prod->setCluster($cluster);
//        $manager->persist($prod);
//
//        $stag = new Environment();
//        $stag->setName('stag');
//        $stag->setDescription('The staging enviroment');
//        $stag->setDebug(0);
//        $stag->setCluster($cluster);
//        $manager->persist($stag);
//
//        $dev = new Environment();
//        $dev->setName('dev');
//        $dev->setDescription('The development enviroment');
//        $dev->setDebug(1);
//        $dev->setCluster($cluster);
//        $manager->persist($dev);
//
//        // Component Lists
//        $component = new Component();
//        $component->setCode('evc');
//        $component->setName('Environment component');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/environment-component/');
//        $component->setHelmRepository('https://github.com/ConductionNL/environment-component/api/helm');
//        $manager->persist($component);
//
//        // Setup an installation for above component
//        $installation = new Installation();
//        $installation->setComponent($component);
//        $installation->setDomain($domain);
//        $installation->setEnvironment($dev);
//        $installation->setName($component->getName());
//        $installation->setDescription($component->getDescription());
//        $installation->setHelmVersion('v2.12.3');
//        $manager->persist($installation);
//
//        // Installation staging enviroment
//        $installation = clone $installation;
//        $installation->setEnvironment($stag);
//        $manager->persist($installation);
//
//        // Installation production enviroment
//        $installation = clone $installation;
//        $installation->setEnvironment($prod);
//        $manager->persist($installation);
//
//        $component = new Component();
//        $component->setCode('uc');
//        $component->setName('User Component');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/user-component/');
//        $component->setHelmRepository('https://github.com/ConductionNL/user-component/api/helm/');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('db');
//        $component->setName('Dashboard');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/commonground-dashboard/');
//        $component->setHelmRepository('https://github.com/ConductionNL/commonground-dashboard/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('cgrc');
//        $component->setName('Comonground Registratiecomponent');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/Commongroundregistratiecomponent/');
//        $component->setHelmRepository('https://github.com/ConductionNL/Commongroundregistratiecomponent/api/helm');
//        $manager->persist($component);
//
//
//        $component = new Component();
//        $component->setCode('con-web');
//        $component->setName('Conduction Website');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/conductionwebsite/');
//        $component->setHelmRepository('https://github.com/ConductionNL/conductionwebsite/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('prc');
//        $component->setName('Procesregsitatie Component');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/procesregistratiecomponent/');
//        $component->setHelmRepository('https://github.com/ConductionNL/procesregistratiecomponent/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('ts');
//        $component->setName('Trouwservice');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/trouwservice/');
//        $component->setHelmRepository('https://github.com/ConductionNL/trouwservice/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('mrc');
//        $component->setName('dashboard');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/medewerkercatalogus/');
//        $component->setHelmRepository('https://github.com/ConductionNL/medewerkercatalogus/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('wrc');
//        $component->setName('Webresource Catalogus');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/webresourcecatalogus/');
//        $component->setHelmRepository('https://github.com/ConductionNL/webresourcecatalogus/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('vrc');
//        $component->setName('Verzoektype Catalogus');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/verzoekregistratiecomponent/');
//        $component->setHelmRepository('https://github.com/ConductionNL/verzoekregistratiecomponent/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('vtc');
//        $component->setName('Verzoektype Catalogus');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/verzoektypecatalogus/');
//        $component->setHelmRepository('https://github.com/ConductionNL/verzoektypecatalogus/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('pc');
//        $component->setName('Protocomponent');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/Proto-component-commonground/');
//        $component->setHelmRepository('https://github.com/ConductionNL/Proto-component-commonground/api/helm');
//        $manager->persist($component);
//
//        /*
//        $component = new Component();
//        $component->setCode('db');
//        $component->setName('dashboard');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/proto-application-commonground/');
//        $component->setHelmRepository('https://github.com/ConductionNL/proto-application-commonground/api/helm');
//        $manager->persist($enviroment);
//        */
//
//        $component = new Component();
//        $component->setCode('hp-ui');
//        $component->setName('Huwelijksplanner User Interface');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/huwelijksplanner-ui/');
//        $component->setHelmRepository('https://github.com/ConductionNL/huwelijksplanner-ui/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('ORC');
//        $component->setName('Order Registratie Component');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/orderregistratiecomponent/');
//        $component->setHelmRepository('https://github.com/ConductionNL/orderregistratiecomponent/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('pdc');
//        $component->setName('Producten en Diensten Catalogus');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/productenendienstencatalogus/');
//        $component->setHelmRepository('https://github.com/ConductionNL/productenendienstencatalogus/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('cc');
//        $component->setName('Contacten Catalogus');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/contactcatalogus/');
//        $component->setHelmRepository('hhttps://github.com/ConductionNL/contactcatalogus/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('bs');
//        $component->setName('Berichten Service');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/betaalservice/');
//        $component->setHelmRepository('https://github.com/ConductionNL/betaalservice/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('irc-ui');
//        $component->setName('Instemmingen Registatie Service User Interface');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/instemmingen-interface/');
//        $component->setHelmRepository('hhttps://github.com/ConductionNL/instemmingen-interface/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('irc');
//        $component->setName('Instemmingen Registatie Service');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/instemmingservice/');
//        $component->setHelmRepository('https://github.com/ConductionNL/instemmingservice/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('stuf');
//        $component->setName('Stuf Service');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/stufservice/');
//        $component->setHelmRepository('https://github.com/ConductionNL/stufservice/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('pc-ui');
//        $component->setName('Protocomponent UI');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/Proto-application-NLDesign/');
//        $component->setHelmRepository('https://github.com/ConductionNL/Proto-application-NLDesign/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('bbz-ui');
//        $component->setName('BBZ User Interfase');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/corona-interface/');
//        $component->setHelmRepository('https://github.com/ConductionNL/corona-interface/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('ds-ui');
//        $component->setName('Digispoof UI');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/digispoof-interface/');
//        $component->setHelmRepository('https://github.com/ConductionNL/digispoof-interface/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('dp');
//        $component->setName('Doc Parcer');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/docparser/');
//        $component->setHelmRepository('https://github.com/ConductionNL/docparser/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('rc');
//        $component->setName('Review Componen');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/review-component/');
//        $component->setHelmRepository('https://github.com/ConductionNL/review-component/api/helm');
//        $manager->persist($component);
//
//        $component = new Component();
//        $component->setCode('brp');
//        $component->setName('BRP Service');
//        $component->setDescription('This common ground component describes common ground components');
//        $component->setGithubRepository('https://github.com/ConductionNL/brpservice/');
//        $component->setHelmRepository('https://github.com/ConductionNL/brpservice/api/helm');
//        $manager->persist($component);
//
//        $manager->flush();
    }
}
