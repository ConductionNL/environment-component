<?php


namespace App\Service;

use App\Entity\Installation;
use GuzzleHttp\Client;

class DigitalOceanService
{
    private $client;
    private $headers;
    private $guzzleConfig;

    public function __construct()
    {
        $this->headers = [
            'Authorization'=>'Bearer %ChangeMe%',
            'Content-Type'=>'application/json',
            'Accept'=>'application/json',
        ];
        $this->guzzleConfig = [
            'http_errors' => false,
            'base_uri' => 'https://api.digitalocean.com/v2',
            'timeout' => 4000.0,
            'headers' => $this->headers
        ];
        $this->client = new Client(
            $this->guzzleConfig
        );
    }

    public function getDatabaseClusters() : array
    {
        $response = $this->client->get('/databases');

        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(), true);
        }
        throw new Symfony\Component\HttpKernel\Exception\HttpException($response->getStatusCode(), 'https://api.digitalocean.com/v2/databases'.' returned: '.json_encode($response));
    }
    public function getDatabases($clusterId):array
    {
        $response = $this->client->get("/databases/$clusterId/dbs");
        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(), true);
        }
        throw new Symfony\Component\HttpKernel\Exception\HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/dbs".' returned: '.json_encode($response));
    }
    public function getDatabaseUsers($clusterId):array
    {
        $response = $this->client->get("/databases/$clusterId/users");
        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(), true);
        }
        throw new Symfony\Component\HttpKernel\Exception\HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/users".' returned: '.json_encode($response));
    }
    public function createDatabaseCluster(string $name):array
    {
        $databaseCluster = [
            'name' => $name,
            'engine' => 'pg',
            'version' => '11',
            'region' => 'ams3',
            'size' => 'db-s-1vcpu-1gb',
            'num_nodes' => 1,
            'tags' => [$name]
        ];
        $resource = json_encode($databaseCluster);
        $response = $this->client->post('/databases', ['body'=>$resource]);

        if($response->getStatusCode() == 201){
            return $response->getBody();
        }
        throw new Symfony\Component\HttpKernel\Exception\HttpException($response->getStatusCode(), 'https://api.digitalocean.com/v2/databases'.' returned: '.json_encode($response));
    }
    public function createDatabase($name, $clusterId):array
    {
        $database = [
            'name'=>$name
        ];
        $resource = json_encode($database);
        $response = $this->client->post("/databases/$clusterId/dbs", ['body'=>$resource]);

        if($response->getStatusCode() == 201){
            return $response->getBody();
        }
        throw new Symfony\Component\HttpKernel\Exception\HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/dbs".' returned: '.json_encode($response));
    }
    public function createDatabaseUser($name, $clusterId):array
    {
        $user = [
            'name'=>$name
        ];
        $resource = json_encode($user);
        $response = $this->client->post('/databases', ['body'=>$resource]);

        if($response->getStatusCode() == 201){
            return $response->getBody();
        }
        throw new Symfony\Component\HttpKernel\Exception\HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/users".' returned: '.json_encode($response));
    }
    public function getDatabaseClusterByName($name){
        $dbCluster = [];
        $dbClusters = $this->getDatabaseClusters();
        foreach($dbClusters['databases'] as $dbCl){
            if($dbCl['name'] == $name){
                $dbCluster['id'] = $dbCl['id'];
                $dbCluster['url'] = parse_url($dbCl['connection']['uri']);
            }
        }
        if(empty($dbCluster)){
            $dbCl = $this->createDatabaseCluster($name)['database'];
            $dbCluster['id'] = $dbCl['id'];
            $dbCluster['url'] = parse_url($dbCl['connection']['uri']);
        }
        return $dbCluster;
    }
    public function getDatabaseByName($name, $dbCluster){
        $database = [];
        $dbs = $this->getDatabases($dbCluster['id']);
        foreach($dbs['dbs'] as $db){
            if($db['name'] == $name){
                $database['id'] = $db['id'];
            }
        }
        if(empty($database)){
            $db = $this->createDatabase($name, $dbCluster['id'])['db'];
            $database['name'] = $db['name'];
        }
        return $database;
    }
    public function getDatabaseUserByName($name, $dbCluster){
        $user = [];
        $users = $this->getDatabaseUsers($dbCluster['id']);
        foreach($users as $u){
            if($u['name'] == $name){
                $user['username'] = $u['name'];
                $user['password'] = $u['password'];
            }
        }
        if(empty($database)){
            $u = $this->createDatabaseUser($name, $dbCluster['id']);
            $user['username'] = $u['name'];
            $user['password'] = $u['password'];
        }
        return $user;
    }
    public function createConnectionUrl(Installation $installation){
        $cluster = $installation->getDomain()->getCluster();

        //Check if there is a database cluster with the same name as the kubernetes cluster, else create
        $dbCluster = $this->getDatabaseClusterByName($cluster->getName());

        //Check if there is a database with the same name as the installation, else create
        $installationName = $installation->getName().'-'.$installation->getEnvironment()->getName();

        $database = $this->getDatabaseByName($installationName, $dbCluster);
        $user = $this->getDatabaseUserByName($installationName, $dbCluster);

        $parsedUrl = $dbCluster['url'];
        return "{$parsedUrl['scheme']}://{$user['username']}:{$user['password']}@{$parsedUrl['host']}:{$parsedUrl['port']}/{$database['name']}?sslmode=require&serverVersion=11";
    }
}
