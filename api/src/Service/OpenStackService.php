<?php


namespace App\Service;


use App\Entity\Cluster;
use App\Entity\OpenStackTemplate;
use Doctrine\ORM\EntityManagerInterface;
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
        $process = new Process(['openstack', 'coe', 'cluster', 'config', $name]);
    }
    public function createTemplate(OpenStackTemplate $template):bool{
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
    public function createKubernetesCluster(Cluster $cluster):Cluster{

        $init = false;
        foreach ($this->getTemplates() as $template){
            if($template == $cluster->getTemplate()->getName()){
                $init = true;
                break;
            }
        }
        if(!$init){
            $this->createTemplate($cluster->getTemplate());
        }

        $process = new Process(['openstack','coe', 'create',
            '--cluster-template', $cluster->getTemplate()->getName(),
            '--master-count',1,
            '--node-count',$cluster->getTemplate()->getNodeCount(),
            '--keypair', $cluster->getKeyPair(),
            $cluster->getName()
        ]);

        $process->run();
        $cluster->setStatus('creating');
        if(!$process->isSuccessful()){
            throw new ProcessFailedException($process);
        }
    }
    public function getKubernetesClusters():array{
        $process = new Process(['openstack', 'coe', 'cluster', 'list']);
        $process->run();
        $clusters = [];
        if($process->isSuccessful()) {
            $clusters = explode('\n', $process->getOutput());
            $iterator = 0;
            foreach ($clusters as $k8cluster) {
                if (
                    $iterator > 2
                    && count($array = explode('|', $k8cluster)) > 1
                ){
                    $cluster = ['id'=>trim($array[0]),'name'=>trim($array[1]),'keypair'=>trim($array[2]),'node_count'=>trim($array[3]),'master_count'=>trim($array[4]),'status'=>trim($array[5]),'health_status'=>trim($array[6])];
                    $clusters[] = $cluster;
                }
            }
        }

    }
    public function getStatus(Cluster $cluster){
        foreach($this->getKubernetesClusters() as $k8cluster){
            if($k8cluster['name'] == $cluster->getName()){
                return $cluster['status'];
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
    public function getTemplates():array{
        $process = new Process(['openstack', 'coe', 'template', 'list']);
        $process->run();
        $templates = [];
        $iterator = 0;
        foreach(explode("\n", $process->getOutput()) as $template){
            if($iterator > 2){
                array_push($templates, trim(explode('|', $template)[1]));
            }
            $iterator++;
        }
        return $templates;
    }
}
