<?php


namespace App\Service;

use App\Entity\Cluster;
use App\Entity\Installation;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class DigitalOceanService
{
    private $client;
    private $headers;
    private $guzzleConfig;
    private $params;
    private $em;
    private $kernel;

    public function __construct(ParameterBagInterface $params,EntityManagerInterface $em)
    {
        $this->params = $params;
        $this->headers = [
            'Authorization'=>'Bearer '.$this->params->get('app_digitalocean_key'),
            'Content-Type'=>'application/json',
            'Accept'=>'application/json',
        ];
        $this->guzzleConfig = [
            'http_errors' => false,
            'base_uri' => 'https://api.digitalocean.com/v2/',
            'timeout' => 4000.0,
            'headers' => $this->headers
        ];
        $this->client = new Client(
            $this->guzzleConfig
        );
        $this->em = $em;
    }
    public function getKubernetesClusters() : array
    {
        $response = $this->client->get('kubernetes/clusters');

        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(),true);
        }
        throw new HttpException($response->getStatusCode(), 'https://api.digitalocean.com/v2/kubernetes/clusters'.' returned: '.$response->getBody());
    }
    public function getDatabaseClusters() : array
    {
        $response = $this->client->get('databases');
//        var_dump($this->guzzleConfig);
//        var_dump($this->params->get('app_digitalocean_key'));
//        var_dump($response);
//        die;

        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(), true)['databases'];
        }
        throw new HttpException($response->getStatusCode(), 'https://api.digitalocean.com/v2/databases'.' returned: '.$response->getBody());
    }
    public function getDatabases($clusterId):array
    {
        $response = $this->client->get("databases/$clusterId/dbs");
        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(), true);
        }
        throw new HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/dbs".' returned: '.$response->getBody());
    }
    public function getDatabaseUsers($clusterId):array
    {
        $response = $this->client->get("databases/$clusterId/users");
        if($response->getStatusCode() == 200){
            return json_decode($response->getBody(), true);
        }
        throw new HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/users".' returned: '.$response->getBody());
    }
    public function getKubernetesCredentials(string $id){
        $response = $this->client->get("kubernetes/clusters/$id/kubeconfig");
        if($response->getStatusCode() == 200){
            file_put_contents(dirname(__FILE__).'/kubeconfig.yaml',$response->getBody());
            return dirname(__FILE__).'/kubeconfig.yaml';
        }
        throw new HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/kubernetes/clusters/$id/credentials".' returned: '.$response->getBody());
    }
    public function configureCluster(array $cluster){
        $kubeconfig = $this->getKubernetesCredentials($cluster['id']);

        $process1 = new Process(["kubectl","-n","kube-system","create","serviceaccount","tiller","--kubeconfig={$kubeconfig}"]);
        $process1->run();
        if(!$process1->isSuccessful()){
            throw new ProcessFailedException($process1);
        }
        $process2 = new Process(["kubectl","create","clusterrolcebinding","tiller","--clusterrole cluster-admin","--serviceaccount:kube-system:tiller","--kubeconfig={$kubeconfig}"]);
        $process2->run();
        if(!$process2->isSuccessful()){
            throw new ProcessFailedException($process2);
        }
        $process3 = new Process(["helm","init","--service-account tiller","--kubeconfig={$kubeconfig}"]);
        $process3->run();
        if(!$process3->isSuccessful()){
            throw new ProcessFailedException($process3);
        }
        $process4 = new Process(["helm", "install stable/kubernetes-dashboard", "--name dashboard", "--kubeconfig={$kubeconfig}", '--namespace="kube-system"']);
        $process4->run();
        if(!$process4->isSuccessful()){
            throw new ProcessFailedException($process4);
        }

    }
    public function createKubernetesCluster(string $name):array
    {
        $kubernetesCluster = [
            'name' => $name,
            'region' => 'ams3',
            'version' => 'latest',
            'node_pools' => [[
                'size' => 's-4vcpu-8gb',
                'count' => 3,
                'name' => "pool-$name-heavy",
            ]],
            'auto_upgrade' => true,
            'maintenance_policy' => [
                'start_time'=> "00:00",
                'day'=>"any"
            ]
        ];
        $resource = json_encode($kubernetesCluster);
        $response = $this->client->post('kubernetes/clusters', ['body'=>$resource]);
        if($response->getStatusCode() == 201){
            $cluster = json_decode($response->getBody(),true)['kubernetes_cluster'];
            while($cluster['status']['state'] != 'running'){
                sleep(5);
                $cluster = json_decode($this->client->get("kubernetes/clusters/{$cluster['id']}")->getBody(),true)['kubernetes_cluster'];
                echo $cluster['status']['state'];
            }
            $this->configureCluster(json_decode($response->getBody(), true)['kubernetes_cluster']);
            return $cluster;
        }
        throw new HttpException($response->getStatusCode(), 'https://api.digitalocean.com/v2/kubernetes/clusters'.' returned: '.$response->getBody());
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
        $response = $this->client->post('databases', ['body'=>$resource]);

        if($response->getStatusCode() == 201){
            $cluster = json_decode($response->getBody(),true)['database'];
            while($cluster['status'] != 'online'){
                sleep(5);
                $cluster = json_decode($this->client->get("databases/{$cluster['id']}")->getBody(), true)['database'];
            }
            return $cluster;
        }
        throw new HttpException($response->getStatusCode(), 'https://api.digitalocean.com/v2/databases'.' returned: '.$response->getBody());
    }
    public function createDatabase($name, $clusterId):array
    {
        $database = [
            'name'=>$name
        ];
        $resource = json_encode($database);
        $response = $this->client->post("databases/$clusterId/dbs", ['body'=>$resource]);

        if($response->getStatusCode() == 201){
            return json_decode($response->getBody(),true);
        }
        throw new HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/dbs".' returned: '.$response->getBody().'on request'.$resource);
    }
    public function createDatabaseUser($name, $clusterId):array
    {
        $user = [
            'name'=>$name
        ];
        $resource = json_encode($user);
        $response = $this->client->post("databases/$clusterId/users", ['body'=>$resource]);

        if($response->getStatusCode() == 201){
            return json_decode($response->getBody(), true);
        }
        throw new HttpException($response->getStatusCode(), "https://api.digitalocean.com/v2/databases/$clusterId/users".' returned: '.$response->getBody().'on request'.$resource);
    }
    public function getKubernetesClusterByName(string $name):array
    {

        foreach($this->getKubernetesClusters()['kubernetes_clusters'] as $k8cluster){
            if($k8cluster['name'] == $name){
                return $k8cluster;
            }
        }
        $k8cluster = $this->createKubernetesCluster($name)['kubernetes_cluster'];
//        $k8cluster['new'] = true;
        return $k8cluster;
    }

    public function getDatabaseClusterByName($name){
        $dbCluster = [];
        $dbClusters = $this->getDatabaseClusters();
        foreach($dbClusters as $dbCl){
            if($dbCl['name'] == $name){
                $dbCluster['id'] = $dbCl['id'];
                $dbCluster['url'] = parse_url($dbCl['connection']['uri']);
                break;
            }
        }
        if(empty($dbCluster)){
            $dbCl = $this->createDatabaseCluster($name);
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
                $database['name'] = $db['name'];
                break;
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
//        var_dump($users['users']);
        foreach($users['users'] as $u){
            if($u['name'] == $name){
                $user['username'] = $u['name'];
                $user['password'] = $u['password'];
                break;
            }
        }
        if(empty($user)){
            $u = $this->createDatabaseUser($name, $dbCluster['id'])['user'];
            $user['username'] = $u['name'];
            $user['password'] = $u['password'];
        }
        return $user;
    }

    public function createConnectionUrl(Installation $installation){
        $cluster = $installation->getDomain()->getCluster();

        //Check if there is a database cluster with the same name as the kubernetes cluster, else create
        $dbCluster = $this->getDatabaseClusterByName($cluster->getName());
//        var_dump($dbCluster['id']);
        //Check if there is a database with the same name as the installation, else create
        $installationName = $installation->getComponent()->getCode().'-'.$installation->getEnvironment()->getName();

        $database = $this->getDatabaseByName($installationName, $dbCluster);
        $user = $this->getDatabaseUserByName($installationName, $dbCluster);

        $parsedUrl = $dbCluster['url'];

        $installation->setDbUrl("{$parsedUrl['scheme']}://{$user['username']}:{$user['password']}@{$parsedUrl['host']}:{$parsedUrl['port']}/{$database['name']}?sslmode=require&serverVersion=11");

        $this->em->persist($installation);
        $this->em->flush();

        return $installation;
    }
    public function createKubeConfig(Cluster $cluster){
        $k8cluster = $this->getKubernetesClusterByName($cluster->getName());
        $kubeconfig = $this->getKubernetesCredentials($k8cluster['id']);

        $cluster->setKubeconfig(file_get_contents($kubeconfig));
        unlink($kubeconfig);
        return $cluster;

    }
}
