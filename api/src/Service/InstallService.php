<?php

namespace App\Service;

use App\Entity\Installation;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InstallService
{
    private $digitalOceanService;
    private $commonGroundService;
    private $clusterService;
    private $client;
    private $em;
    private $params;

    public function __construct(ParameterBagInterface $params, DigitalOceanService $digitalOceanService, CommonGroundService $commonGroundService, EntityManagerInterface $em, ClusterService $clusterService)
    {
        $this->digitalOceanService = $digitalOceanService;
        $this->commonGroundService = $commonGroundService;
        $this->clusterService = $clusterService;
        $this->client = new Client();
        $this->em = $em;
        $this->params = $params;
    }

    public function delete(Installation $installation)
    {
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());

        try {
            $result = $this->clusterService->deleteComponent($installation);
            $installation->setDateInstalled(null);
            $this->em->persist($installation);

            return $result;
        } catch (ProcessFailedException $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update(Installation $installation, string $environment = null)
    {
        // Als we geen db url hebben url maken
//        if(!$installation->getDbUrl()){
        $installation = $this->digitalOceanService->createConnectionUrl($installation);
//        }
        if ($environment && $installation->getEnvironment()->getName() != $environment) {
            return 'Installation not in environment';
        }

        // Altijd een nieuwe kubeconfig ophalen
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());

        try {
            $result = $this->clusterService->upgradeComponent($installation);
            $result = $this->clusterService->restartComponent($installation);
            $installation->setDateInstalled(new \DateTime('now'));
            $this->em->persist($installation);
            $this->em->flush();

            return $result;
        } catch (ProcessFailedException $error) {
            throw new HttpException(500, $error->getMessage());
        }
    }

    public function rollingUpdate(Installation $installation)
    {
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());

        try {
            return $this->clusterService->restartComponent($installation);
        } catch (ProcessFailedException $error) {
            throw new HttpException(500, $error->getMessage());
        }
    }

    public function install(Installation $installation, string $environment = null)
    {
        // Als we geen db url hebben url maken
        if (!$installation->getDbUrl()) {
            $installation = $this->digitalOceanService->createConnectionUrl($installation);
        }
        if ($environment && $installation->getEnvironment()->getName() != $environment) {
            return 'Installation not in environment';
        }

        // Altijd een nieuwe kubeconfig ophalen
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());
        if (!in_array($installation->getEnvironment()->getName(), $this->clusterService->getNamespaces($installation->getEnvironment()->getCluster()))) {
            $this->clusterService->createNamespace($installation->getEnvironment());
        }

        try {
            $result = $this->clusterService->installComponent($installation);
            $installation->setDateInstalled(new \DateTime('now'));
            $this->em->persist($installation);
            $this->em->flush();

            return $result;
        } catch (ProcessFailedException $error) {
            throw new HttpException(500, $error->getMessage());
        }
    }
}
