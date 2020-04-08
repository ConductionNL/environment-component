<?php


namespace App\Service;


use App\Entity\Cluster;
use App\Entity\Component;
use App\Entity\Domain;
use App\Entity\Environment;
use App\Entity\Installation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ExcelService
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
    public function containsClusterEnvironmentName(Cluster $cluster, string $environmentName) : bool
    {
        if($cluster->getEnvironments() == null){
            return false;
        }
        foreach($cluster->getEnvironments() as $environment){
            if($environment->getName() == $environmentName)
                return true;
        }
        return false;
    }
    public function containsEnvironmentComponent(Environment $environment, Component $component) : bool
    {
        foreach($environment->getInstallations() as $installation){
            if($installation->getComponent()->getCode() == $component->getCode())
                return true;
        }
        return false;
    }
    public function loadComponentsFromSpreadsheet(
        Worksheet $components,
        Domain $domain,
        ArrayCollection $environments,
        EntityManager $manager
    ){
        $j=0;
        foreach($components->toArray() as $componentArray){
            if($j >= $components->getHighestRow()){
                break;
            }
            elseif(count($manager->getRepository('App:Component')->findBy(['code' => $componentArray[0]]))>0){
                continue;
            }
            var_dump(count($manager->getRepository('App:Component')->findBy(['code' => $componentArray[0]])));
            var_dump(gettype($manager->getRepository('App:Component')->findBy(['code' => $componentArray[0]])));
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
                if($this->containsEnvironmentComponent($environment, $component)){
                    continue;
                }
                if($componentArray[5] == 1){
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
            }

            $j++;
        }
    }
    public function loadEnvironmentsFromSpreadsheet(
        Worksheet $environments,
        Cluster $cluster,
        EntityManager $manager
    ){
        $envs = new ArrayCollection();
        $i = 0;
        foreach($environments->toArray() as $environmentArray) {
            if ($i >= $environments->getHighestRow()) {
                break;
            }elseif($this->containsClusterEnvironmentName($cluster, $environmentArray[0])){
                continue;
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
        EntityManager $manager
    ){
        $i = 0;
        foreach($clusters->toArray() as $clusterArray) {
            if ($i >= $clusters->getHighestRow()) {
                break;
            }
            elseif (count($manager->getRepository('App:Cluster')->findBy(['name' => $clusterArray[0]]))>0){
                continue;
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
    public function loadFromSpreadsheet(string $filename, EntityManager $manager){
        $spreadSheet = $this->loadXlsx($filename);

        $clusters = $spreadSheet->getSheetByName('clusters');
        $components = $spreadSheet->getSheetByName('components');
        $environments = $spreadSheet->getSheetByName('environments');

        $this->loadClustersFromSpreadsheet($clusters, $components, $environments, $manager);


    }

    public function load(EntityManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        if (strpos($this->params->get('app_domain'), "conduction.nl") == false) {
            //return false;
        }
        $this->loadFromSpreadsheet(dirname(__FILE__) . '/resources/components.xlsx', $manager);
    }
}
