<?php


namespace App\Service;


use App\Entity\Cluster;
use App\Entity\Domain;
use App\Entity\Record;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CloudFlareService
{
    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, ClusterService $clusterService)
    {
        $this->params = $params;
        $this->headers = [
            'X-Auth-Key'=> $this->params->get('app_cloudflare_key'),
            'X-Auth-Email'=>$this->params->get('app_cloudflare_email'),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
        $this->guzzleConfig = [
            'http_errors' => false,
            'base_uri'    => 'https://api.cloudflare.com/client/v4/',
            'timeout'     => 4000.0,
            'headers'     => $this->headers,
        ];
        $this->client = new Client(
            $this->guzzleConfig
        );
        $this->em = $em;
        $this->clusterService = $clusterService;
    }

    public function addDNSRecord($name, $ip)
    {
        if(strpos($name,'commonground.nu') === false){
            return false;
        }

        $data = [
            'type'=>'A',
            'name'=>$name,
            'content'=>$ip,
            'ttl'=>120,
            'proxied'=>false
        ];
//        var_dump($data);
        $response = $this->client->post('zones/b8de3ea289b7c1f89d6b214531757bbf/dns_records',['body'=>json_encode($data)]);
        if ($response->getStatusCode() != 200) {
            throw new HttpException($response->getStatusCode(), "zones/b8de3ea289b7c1f89d6b214531757bbf/dns_records".' returned: '.$response->getBody());
        }
//        var_dump(json_decode($response->getBody(),true));
        return json_decode($response->getBody(),true)['result']['id'];
    }
    public function removeDNSRecord($id)
    {
        if($id){
            $response = $this->client->delete("zones/b8de3ea289b7c1f89d6b214531757bbf/dns_records/$id");
            if ($response->getStatusCode() != 200) {
                throw new HttpException($response->getStatusCode(), "zones/b8de3ea289b7c1f89d6b214531757bbf/dns_records".' returned: '.$response->getBody());
            }
        }

        return true;
    }
    public function removeDNSRecordsForDomain(Domain $domain){
        foreach($domain->getRecords() as $record){
            if($this->removeDNSRecord($record->getCloudFlareId())){
                $domain->removeRecord($record);
            }
        }
        return $domain;
    }
    public function createDNSRecordsForDomain(Domain $domain){


        if($id = $this->addDNSRecord($domain->getName(),$domain->getCluster()->getIp())){
            $record = new Record();
            $record->setCloudFlareId($id);
            $domain->addRecord($record);
        }

        if($id = $this->addDNSRecord("*.{$domain->getName()}",$domain->getCluster()->getIp())){
            $record = new Record();
            $record->setCloudFlareId($id);
            $domain->addRecord($record);
        }
        return $domain;
    }
}
