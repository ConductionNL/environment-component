<?php


namespace App\Service;

use GuzzleHttp\Client;
use App\Entity\Cluster;
use App\Entity\Component;
use App\Entity\Domain;
use App\Entity\Environment;

class InstallService
{
    private $commonGroundService;
    private $client;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->client = new Client();
    }

    public function formDbUrl($dbBaseUrl, $dbUsername, $dbPassword, $dbName){
        $parsedUrl = parse_url($dbBaseUrl);
        return "{$parsedUrl['schema']}://$dbUsername:$dbPassword@{$parsedUrl['host']}:{$parsedUrl['port']}/$dbName?sslmode=require&serverVersion=11";
    }

    public function getGithubAPIUrl($repository){
        $parsedUrl = parse_url($repository);
        return "https://api.github.com/repos{$parsedUrl['path']}/dispatches";
    }

    // Volgens mij heb je hier twee functies nodig install/update. En heeft de functie alleen een component nodig (rest zit daar immers al aan vast

    public function update(Component $component)
    {
        $url = $this->getGithubAPIUrl($component->getGithubRepository());
        $request['environment'] = $component->getEnvironment()->getName();
        $request['domain'] = $component->getDomain()->getName();
        $request['dburl'] = $this->formDbUrl($request['domain']->getDatabaseUrl(), $component->getDbUsername(), $component->getDbPassword(), $component->getDbName());
        $request['authorization'] = $component->getAuthorization();
        $request['kubeconfig'] = $component->getEnvironment()->getCluster()->getKubeconfig();
        $request['eventType'] = "start_upgrade_workflow";

        $result = $this->client->post($url,
            [
                'body' => json_encode($request),
                'headers'=>
                    [
                        "Authorization"=> "Bearer ".$component->getGithubToken(),
                        'Content-Type'=>'application/json',
                        'Accept'=>'application/vnd.github.everest-preview+json'
                    ]
            ]
        );
        if($result->getStatusCode() == 204){
            return "Action triggered, check {$component->getGithubRepository()}/actions for the status";
        }
        else{
            throw new Symfony\Component\HttpKernel\Exception\HttpException($result->getStatusCode(), $url.' returned: '.json_encode($result->getBody()));
        }

    }

    public function install(Component $component)
    {
        $url = $this->getGithubAPIUrl($component->getGithubRepository());
        $request['environment'] = $component->getEnvironment()->getName();
        $request['domain'] = $component->getDomain()->getName();
        $request['dburl'] = $this->formDbUrl($request['domain']->getDatabaseUrl(), $component->getDbUsername(), $component->getDbPassword(), $component->getDbName());
        $request['authorization'] = $component->getAuthorization();
        $request['kubeconfig'] = $component->getEnvironment()->getCluster()->getKubeconfig();
        $request['eventType'] = "start_install_workflow";

        $result = $this->client->post($url,
            [
                'body' => json_encode($request),
                'headers'=>
                    [
                        "Authorization"=> "Bearer ".$component->getGithubToken(),
                        'Content-Type'=>'application/json',
                        'Accept'=>'application/vnd.github.everest-preview+json'
                    ]
            ]
        );
        if($result->getStatusCode() == 204){
            return "Action triggered, check {$component->getGithubRepository()}/actions for the status";
        }
        else{
            return 1;
        }
    }
}
