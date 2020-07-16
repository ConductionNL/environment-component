<?php


namespace App\Service;


use App\Entity\Cluster;
use App\Entity\OpenStackTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class OpenStackService
{
    private $em;
    private $kernel;
    private $clusterService;
    private $params;
    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, ClusterService $clusterService){
        $this->params = $params;
        $this->em = $em;
        $this->clusterService = $clusterService;
    }

    public function getKubernetesCredentials(string $name){
        $stamp = microtime();
        mkdir("/var/openstack-$stamp");

        $process = new Process(['openstack', 'coe', 'cluster', 'config', $name,'--dir',"/var/openstack-$stamp"]);
        $process->run();
        if(!$process->isSuccessful() &&  $process->getErrorOutput() !="'SHELL'\n"){
            throw new ProcessFailedException($process);
        }
        $kubeconfig = file_get_contents("/var/openstack-$stamp/config");
        unlink("/var/openstack-$stamp/config");
        rmdir("/var/openstack-$stamp");
        return $kubeconfig;
    }
    public function getTemplates():array{
        echo "collecting templates\n";
        $process = new Process(['openstack', 'coe', 'cluster', 'template', 'list']);
        $process->run();
        $templates = [];
        $iterator = 0;
        foreach(explode("\n", $process->getOutput()) as $template){
            $template = explode('|', $template);
            if($iterator > 2 && count($template)>1){
                array_push($templates, trim($template[2]));
            }
            $iterator++;
        }
        return $templates;
    }
    public function createTemplate(OpenStackTemplate $template):bool{
        echo "creating template \n";
        if(!$template->getNodeFlavour()){
            $nodeFlavour = $template->getMasterFlavour();
        }
        else{
            $nodeFlavour = $template->getNodeFlavour();
        }
        $process = new Process(['openstack','coe','cluster','template','create',
            '--image',$template->getImage(),
            '--external-network','public',
            '--master-flavor',$template->getMasterFlavour(),
            '--flavor',$nodeFlavour,
            '--coe','kubernetes',
            '--volume-driver', 'cinder',
            '--network-driver','flannel',
            '--docker-volume-size',$template->getVolumeSize(),
            '--labels',"kube_dashboard_enabled=0,kube_tag={$template->getVersionTag()}",
            $template->getName(),
        ]);

        $process->run();

        if($process->isSuccessful()){
            return true;
        }else{
            throw new ProcessFailedException($process);
        }
    }
    public function createKeyPair(Cluster $cluster):string{
        $process = new Process(['openstack','keypair','create', $cluster->getName()]);
        $process->run();
        if($process->isSuccessful()){
            echo $process->getOutput();
            return $cluster->getName();
        }else{
            throw new ProcessFailedException($process);
        }
    }

    public function configureCluster(Cluster $cluster): Cluster
    {
        // Make sure we can only try to configure clusters in there installation procces
        if (!$cluster->getStatus() != 'creating') {
            return true;
        }

        if ($this->getStatus($cluster) == 'CREATE_COMPLETE') {
            $cluster->setStatus('installing');
            $cluster->setKubeconfig($this->getKubernetesCredentials($cluster->getName()));

            return $cluster;
        }

        return false;
    }

    public function createKubernetesCluster(Cluster $cluster):Cluster{

        echo "creating Kubernetes Cluster ".$cluster->getName()."\n";

        $init = false;
        foreach ($this->getTemplates() as $template){
            //echo $template;
            if($template == $cluster->getTemplate()->getName()){
                $init = true;
                break;
            }
        }
        if(!$init){
            $this->createTemplate($cluster->getTemplate());
        }
        if(!$cluster->getKeyPair()){
            $keypair = $this->createKeyPair($cluster);
        }else{
            $keypair = $cluster->getKeyPair();
        }

        echo 'create cluster';
        $process = new Process(['openstack','coe','cluster', 'create',
            '--cluster-template', $cluster->getTemplate()->getName(),
            '--master-count',1,
            '--node-count',$cluster->getTemplate()->getNodeCount(),
            '--keypair', $keypair,
            $cluster->getName()
        ]);

        $process->run();
        $cluster->setStatus('creating');
        foreach($this->getKubernetesClusters() as $k8cluster){
            if($cluster->getName() == $k8cluster['name']){
                $cluster->setProviderId($k8cluster['id']);
            }
        }
        $this->em->persist($cluster);
        $this->em->flush();
        if(!$process->isSuccessful()){
            throw new ProcessFailedException($process);
        }

        $this->configureCluster($cluster);

        return $cluster;
    }
    public function getKubernetesClusters():array{
        $process = new Process(['openstack', 'coe', 'cluster', 'list']);
        $process->run();
        $clusters = [];
        if($process->isSuccessful()) {
            $k8clusters = explode("\n", $process->getOutput());
            $iterator = 0;
            foreach ($k8clusters as $k8cluster) {
                if (
                    $iterator > 2
                    && count($array = explode('|', $k8cluster)) > 1
                ){
                    $cluster = ['id'=>trim($array[1]),'name'=>trim($array[2]),'keypair'=>trim($array[3]),'node_count'=>trim($array[4]),'master_count'=>trim($array[5]),'status'=>trim($array[6]),'health_status'=>trim($array[7])];
                    $clusters[] = $cluster;
                }
                $iterator++;
            }
        }
        return $clusters;
    }
    public function getStatus(Cluster $cluster){
        foreach($this->getKubernetesClusters() as $k8cluster){
            if($k8cluster['name'] == $cluster->getName()){
                return $k8cluster['status'];
            }
        }

        return false;
    }
    public function getKubernetesClusterByName(Cluster $cluster): Cluster
    {
        foreach ($this->getKubernetesClusters() as $k8cluster) {
            if ($k8cluster['name'] == $cluster->getName()) {
                $cluster->setKubeconfig($this->getKubernetesCredentials($k8cluster['name']));

                return $cluster;
            }
        }
        $k8cluster = $this->createKubernetesCluster($cluster);
        return $k8cluster;
    }
    public function createKubeConfig(Cluster $cluster){
        $cluster = $this->getKubernetesClusterByName($cluster);
        $this->em->persist($cluster);
        $this->em->flush();

        return $cluster;
    }
}
