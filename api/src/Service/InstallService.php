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

class InstallService
{
    private $digitalOceanService;
    private $commonGroundService;
    private $client;
    private $em;
    private $params;

    public function __construct(ParameterBagInterface $params, DigitalOceanService $digitalOceanService, CommonGroundService $commonGroundService,EntityManagerInterface $em)
    {
        $this->digitalOceanService = $digitalOceanService;
        $this->commonGroundService = $commonGroundService;
        $this->client = new Client();
        $this->em = $em;
        $this->params = $params;
    }

    public function formDbUrl($dbBaseUrl, $dbUsername, $dbPassword, $dbName){
        $parsedUrl = parse_url($dbBaseUrl);
        return "{$parsedUrl['scheme']}://$dbUsername:$dbPassword@{$parsedUrl['host']}:{$parsedUrl['port']}/$dbName?sslmode=require&serverVersion=11";
    }

    public function getGithubAPIUrl($repository){
        $parsedUrl = parse_url($repository);
        return "https://api.github.com/repos{$parsedUrl['path']}/dispatches";
    }

    // Volgens mij heb je hier twee functies nodig install/update. En heeft de functie alleen een component nodig (rest zit daar immers al aan vast

    public function update(Installation $installation)
    {
        // Als we geen db url hebben url maken
        if(!$installation->getDbUrl()){
            $installation =  $this->digitalOceanService->createConnectionUrl($installation);
        }

        // Als we geen kubeconfig hebben deze aanmaken
        if(!$installation->getEnvironment()->getCluster()->getKubeconfig()){
            $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());
        }

        $url = $this->getGithubAPIUrl($installation->getComponent()->getGithubRepository());
        $data['environment'] = $installation->getEnvironment()->getName();
        $data['domain'] = $installation->getDomain()->getName();
        $data['dburl'] = $installation->getDbUrl();
        //$data['dburl'] = $this->formDbUrl($component->getDomain()->getDatabaseUrl(), $component->getDbUsername(), $component->getDbPassword(), $component->getDbName());
        $data['authorization'] = $installation->getAuthorization();
        $data['kubeconfig'] = $installation->getEnvironment()->getCluster()->getKubeconfig();

        $request['event_type'] = "start-upgrade-workflow";
        $request['client_payload'] = $data;

        $token = $installation->getComponent()->getGithubToken();

        // Default to general github key
        if(!$token){
            $token = $this->params->get('app_github_key');
        }
        var_dump($token);

        $result = $this->client->post($url,
            [
                'body' => json_encode($request),
                'headers'=>
                    [
                        "Authorization"=> "Bearer ".$token,
                        'Content-Type'=>'application/json',
                        'Accept'=>'application/vnd.github.everest-preview+json'
                    ]
            ]
        );

        if($result->getStatusCode() == 204){
            $installation->setDateInstalled(new \DateTime("now"));
            $this->em->persist($installation);
            $this->em->flush();
            return "Action triggered, check {$installation->getComponent()->getGithubRepository()}/actions for the status";
        }
        else{
            throw new Symfony\Component\HttpKernel\Exception\HttpException($result->getStatusCode(), $url.' returned: '.json_encode($result->getBody()));
        }

    }

    public function install(Installation $installation)
    {
        // Als we geen db url hebben url maken
        if(!$installation->getDbUrl()){
            $installation =  $this->digitalOceanService->createConnectionUrl($installation);
        }

        // Als we geen kubeconfig hebben deze aanmaken
        if(!$installation->getEnvironment()->getCluster()->getKubeconfig()){
            $this->digitalOceanService->createKubeConfig($installation->getEnvironment()->getCluster());
        }
        $url = $this->getGithubAPIUrl($installation->getComponent()->getGithubRepository());
        $data['environment'] = $installation->getEnvironment()->getName();
        $data['domain'] = $installation->getDomain()->getName();
        $data['dburl'] = $installation->getDbUrl();
        //$data['dburl'] = $this->formDbUrl($component->getDomain()->getDatabaseUrl(), $component->getDbUsername(), $component->getDbPassword(), $component->getDbName());
        $data['authorization'] = $installation->getAuthorization();
        $data['kubeconfig'] = $installation->getEnvironment()->getCluster()->getKubeconfig();

        $request['event_type'] = "start-install-workflow";
        $request['client_payload'] = $data;

        $token = $installation->getComponent()->getGithubToken();
        if(!$token){
            $token = $this->params->get('app_github_key');
        }

        $result = $this->client->post($url,
            [
                'body' => json_encode($request),
                'headers'=>
                    [
                        "Authorization"=> "Bearer ".$token,
                        'Content-Type'=>'application/json',
                        'Accept'=>'application/vnd.github.everest-preview+json'
                    ]
            ]
        );
        if($result->getStatusCode() == 204){
            $installation->setDateInstalled(new \DateTime("now"));
            $this->em->persist($installation);
            $this->em->flush();
            return "Action triggered, check {$installation->getComponent()->getGithubRepository()}/actions for the status";
        }
        else{
            throw new Symfony\Component\HttpKernel\Exception\HttpException($result->getStatusCode(), $url.' returned: '.json_encode($result->getBody()));
        }
    }
}
