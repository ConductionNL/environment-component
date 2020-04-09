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

    /**
     * Creates a reader for Xlsx files
     *
     * @return PhpSpreadsheet\Reader\Xlsx
     */
    public function createReader(): PhpSpreadsheet\Reader\Xlsx
    {
        $reader = new PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);

        return $reader;
    }

    /**
     * Actually loads the Xlsx file
     *
     * @param string $filename
     * @return PhpSpreadsheet\Spreadsheet
     */
    public function loadXlsx(string $filename): PhpSpreadsheet\Spreadsheet
    {
        $reader = $this->createReader();

        try {
            return $reader->load($filename);
        } catch (PhpSpreadsheet\Reader\Exception $e) {
        }

        return null;
    }

    /**
     * Loads environments
     *
     * @param Worksheet $environments
     * @param Cluster $cluster
     * @param EntityManager $manager
     * @return ArrayCollection
     * @throws \Doctrine\ORM\ORMException
     */
    public function loadEnvironmentsFromSpreadsheet(
        Worksheet $environments,
        Cluster $cluster,
        EntityManager $manager
    ){
        $envs = new ArrayCollection();
        foreach($environments->toArray() as $environmentArray) {
            /**
             * The environmentArray is build as follows:
             *
             * [0]: Column A: the name of the environment
             * [1]: Column B: the description of the environment
             * [2]: Column C: an integer indicating if the components in the environment should have debugging on
             * [3]: Column D: the authorization token of the environment
             */
            if (!$environmentArray[0]) {
                continue;
            }elseif($cluster->hasEnvironment($environmentArray[0])){
                continue;
            }
            $env = new Environment();
            $env->setName($environmentArray[0]);
            $env->setDescription($environmentArray[1]);
            if($environmentArray[2]){
                $env->setDebug($environmentArray[2]);
            }
            $env->setAuthorization($environmentArray[3]);
            $env->setCluster($cluster);
            $manager->persist($env);
            $envs->add($env);
        }
        return $envs;
    }

    /**
     * Loads components and installations
     *
     * @param Worksheet $components
     * @param Domain $domain
     * @param ArrayCollection $environments
     * @param EntityManager $manager
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function loadComponentsFromSpreadsheet(
        Worksheet $components,
        Domain $domain,
        ArrayCollection $environments,
        EntityManager $manager
    ){
        $iterator=0;

        foreach($components->toArray() as $componentArray){
            /**
             * The componentArray is build as follows:
             *
             * [0]: Column A: the code of the component
             * [1]: Column B: the name of the component
             * [2]: Column C: the description of the component
             * [3]: Column D: the github repository for the component
             * [4]: Column E: the helm repository for the component
             * [5]: Column F: a boolean indicating if the component is a core component
             * [6]: Column G: a date that indicates the component has been installed before
             */
            // We only read the component if the required parameters are present
            if(!$componentArray[0] || !$componentArray[1] || !$componentArray[3] || !$componentArray[4]){
                continue;
            }
            // Components should have a unique code. If a component with this code is already in the database, we skip it
            elseif(count($manager->getRepository('App:Component')->findBy(['code' => $componentArray[0]]))>0){
                continue;
            }
            // Component Lists
            $component = new Component();
            $component->setCode($componentArray[0]);
            $component->setName($componentArray[1]);
            $component->setDescription($componentArray[2]);
            $component->setGithubRepository($componentArray[3]);
            $component->setHelmRepository($componentArray[4]);
            $component->setCore((bool)$componentArray[5]);
            $manager->persist($component);

            // Setup an installation for above component
            if($component->getCore()){
                foreach($environments as $environment){
                    if($component->hasInstallationInEnvironment($environment)){
                        continue;
                    }
                    $installation = new Installation();
                    $installation->setComponent($component);
                    $installation->setDomain($domain);
                    $installation->setEnvironment($environment);
                    $installation->setAuthorization($environment->getAuthorization());
                    $installation->setName($component->getName());
                    $installation->setDescription($component->getDescription());
                    $installation->setHelmVersion('v2.12.3');
                    if($componentArray[6] != null){
                        $installation->setDateInstalled(new \DateTime($componentArray[6]));
                    }
                    $manager->persist($installation);
                }
            }
            if($iterator > 25){
                $manager->flush();
            }
            $iterator++;
        }
    }

    /**
     * Loads clusters and domains
     *
     * @param Worksheet $clusters
     * @param Worksheet $components
     * @param Worksheet $environments
     * @param EntityManager $manager
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function loadClustersFromSpreadsheet(
        Worksheet $clusters,
        Worksheet $components,
        Worksheet $environments,
        EntityManager $manager
    ){
        foreach($clusters->toArray() as $clusterArray) {
            /**
             * The componentArray is build as follows:
             *
             * [0]: Column A: the name of the cluster and the domain
             * [1]: Column B: the description of the cluster
             * [2]: Column C: the description of the domain
             * [3]: Column D: the url for the domain
             */
            if (!$clusterArray[0] || !$clusterArray[3]) {
                continue;
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
        }
    }

    /**
     * Divides the spreadsheet in worksheets to be parsed
     *
     * @param string $filename
     * @param EntityManager $manager
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function loadFromSpreadsheet(string $filename, EntityManager $manager){
        $spreadSheet = $this->loadXlsx($filename);

        $clusters = $spreadSheet->getSheetByName('clusters');
        $components = $spreadSheet->getSheetByName('components');
        $environments = $spreadSheet->getSheetByName('environments');

        $this->loadClustersFromSpreadsheet($clusters, $components, $environments, $manager);
    }

    /**
     * Defines the filename and starts the loading procedure
     * 
     * @param EntityManager $manager
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function load(EntityManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        $this->loadFromSpreadsheet(dirname(__FILE__) . '/resources/components.xlsx', $manager);
    }
}
