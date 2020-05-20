<?php


namespace App\Service;

use GuzzleHttp\Client;
use App\Entity\Cluster;
use App\Entity\Component;
use App\Entity\Domain;
use App\Entity\Environment;
use App\Entity\Installation;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\DigitalOceanService;
use App\Service\CommonGroundService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class InstallService
{
    private $digitalOceanService;
    private $commonGroundService;
    private $clusterService;
    private $client;
    private $em;
    private $params;

    public function __construct(ParameterBagInterface $params, DigitalOceanService $digitalOceanService, CommonGroundService $commonGroundService,EntityManagerInterface $em, ClusterService $clusterService)
    {
        $this->digitalOceanService = $digitalOceanService;
        $this->commonGroundService = $commonGroundService;
        $this->clusterService = $clusterService;
        $this->client = new Client();
        $this->em = $em;
        $this->params = $params;
    }

    public function getGithubAPIUrl($repository){
        $parsedUrl = parse_url($repository);
        return "https://api.github.com/repos{$parsedUrl['path']}/dispatches";
    }

    // Volgens mij heb je hier twee functies nodig install/update. En heeft de functie alleen een component nodig (rest zit daar immers al aan vast
    public function delete(Installation $installation)
    {
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());
        return $this->clusterService->deleteComponent($installation);

    }
    public function update(Installation $installation, string $environment = null)
    {
        // Als we geen db url hebben url maken
        if(!$installation->getDbUrl()){
            $installation =  $this->digitalOceanService->createConnectionUrl($installation);
        }
        if($environment && $installation->getEnvironment()->getName() != $environment){
            return 'Installation not in environment';
        }

        // Altijd een nieuwe kubeconfig ophalen
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());

//        $url = $this->getGithubAPIUrl($installation->getComponent()->getGithubRepository());
//        $data['environment'] = $installation->getEnvironment()->getName();
//        $data['domain'] = $installation->getDomain()->getName();
//        $data['dburl'] = $installation->getDbUrl();
//        $data['debug'] = $installation->getEnvironment()->getDebug();
//        $data['authorization'] = $installation->getAuthorization();
//        if($data['authorization'] == null){
//            $data['authorization'] = $installation->getEnvironment()->getAuthorization();
//        }
//        $data['kubeconfig'] = $installation->getEnvironment()->getCluster()->getKubeconfig();
//
//        $request['event_type'] = "start-upgrade-workflow";
//        $request['client_payload'] = $data;
//
//        $token = $installation->getComponent()->getGithubToken();
//
//        // Default to general github key
//        if(!$token){
//            $token = $this->params->get('app_github_key');
//        }
////        var_dump($token);
//
//        $result = $this->client->post($url,
//            [
//                'body' => json_encode($request),
//                'headers'=>
//                    [
//                        "Authorization"=> "Bearer ".$token,
//                        'Content-Type'=>'application/json',
//                        'Accept'=>'application/vnd.github.everest-preview+json'
//                    ]
//            ]
//        );
        $result = $this->clusterService->upgradeComponent($installation);

        if($result){
            $installation->setDateInstalled(new \DateTime("now"));
            $this->em->persist($installation);
            $this->em->flush();
            return $result;
        }
        else{
            throw new Symfony\Component\HttpKernel\Exception\HttpException(500);
        }

    }

    public function install(Installation $installation, string $environment = null)
    {
        // Als we geen db url hebben url maken
        if(!$installation->getDbUrl()){
            $installation =  $this->digitalOceanService->createConnectionUrl($installation);
        }
        if($environment && $installation->getEnvironment()->getName() != $environment){
            return 'Installation not in environment';
        }

        // Altijd een nieuwe kubeconfig ophalen
        $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());
//        $url = $this->getGithubAPIUrl($installation->getComponent()->getGithubRepository());
//        $data['environment'] = $installation->getEnvironment()->getName();
//        $data['domain'] = $installation->getDomain()->getName();
//        $data['dburl'] = $installation->getDbUrl();
//        $data['debug'] = $installation->getEnvironment()->getDebug();
//        $data['authorization'] = $installation->getAuthorization();
//        if($data['authorization'] == null){
//            $data['authorization'] = $installation->getEnvironment()->getAuthorization();
//        }
//        $data['kubeconfig'] = $installation->getEnvironment()->getCluster()->getKubeconfig();
//
//        $request['event_type'] = "start-install-workflow";
//        $request['client_payload'] = $data;
//
//        $token = $installation->getComponent()->getGithubToken();
//        if(!$token){
//            $token = $this->params->get('app_github_key');
//        }
//
//        $result = $this->client->post($url,
//            [
//                'body' => json_encode($request),
//                'headers'=>
//                    [
//                        "Authorization"=> "Bearer ".$token,
//                        'Content-Type'=>'application/json',
//                        'Accept'=>'application/vnd.github.everest-preview+json'
//                    ]
//            ]
//        );
        $this->clusterService->getNamespaces($installation->getEnvironment()->getCluster());


        $result = $this->clusterService->installComponent($installation);
        if($result){    //TODO: try-catch instead of if/else
            $installation->setDateInstalled(new \DateTime("now"));
            $this->em->persist($installation);
            $this->em->flush();
            return $result;
        }
        else{
            throw new Symfony\Component\HttpKernel\Exception\HttpException(500);
        }
    }
}
