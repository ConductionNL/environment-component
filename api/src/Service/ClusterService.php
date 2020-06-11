<?php

namespace App\Service;

use App\Entity\Cluster;
use App\Entity\Environment;
use App\Entity\Installation;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ClusterService
{
    public function writeKubeconfig(Cluster $cluster)
    {
        file_put_contents(dirname(__FILE__, 3).'/var/kubeconfig.yaml', $cluster->getKubeconfig());

        return dirname(__FILE__, 3).'/var/kubeconfig.yaml';
    }

    public function removeKubeconfig(string $filename)
    {
        unlink($filename);
    }

    public function createCluster(Cluster $cluster)
    {

    }

    public function configureCluster(Cluster $cluster)
    {
        $kubeconfig = $this->writeKubeconfig($cluster);

//        var_dump($cluster->getKubeconfig());

        $configurations = $cluster->getConfigurations();
        $processes = [];
        $errors = [];

        if(!in_array('kubernetes-dashboard', $configurations)){
            echo "Installing kubernetes dashboard\n";
            $process = new Process(['kubectl', 'create', '-f','https://raw.githubusercontent.com/kubernetes/dashboard/v2.0.0/aio/deploy/recommended.yaml', "--kubeconfig={$kubeconfig}"]);
            $process->run();

            if($process->isSuccessful()){
                $configurations[] = 'kubernetes-dashboard';
            }
            else{
                $error = new ProcessFailedException($process);
            }
        }
        if(in_array('kubernetes-dashboard', $configurations) && !in_array('ingress', $configurations)){
            echo "Installing Ingress\n";
            $process = new Process(["helm", "repo","add","stable","https://kubernetes-charts.storage.googleapis.com"]);
            $process->run();

            if(!$process->isSuccessful()){
                $errors[] = new ProcessFailedException($process);
            }

            $process = new Process(["helm", "install","loadbalancer","stable/nginx-ingress","--kubeconfig=$kubeconfig","--namespace=default"]);
            $process->run();

            if(!$process->isSuccessful()){
                $errors[] = new ProcessFailedException($process);
            }

            $process = new Process(['helm', 'upgrade', 'loadbalancer', 'stable/nginx-ingress', "--kubeconfig=$kubeconfig","--namespace=default"]);
            $process->run();
            if($process->isSuccessful()){
                $configurations[] = 'ingress';
            }
            else{
                $error[] = new ProcessFailedException($process);
            }

        }
        if(in_array('ingress', $configurations) && !in_array('cert-manager', $configurations)){
            echo "Installing Cert Manager\n";
            $process = new Process(['helm', 'repo', 'add', 'jetstack', 'https://charts.jetstack.io']);
            $process->run();

            if(!$process->isSuccessful()){
                $errors[] = new ProcessFailedException($process);
            }

            // Creating the name space for the cert manager
            $process = new Process(['kubectl', 'create', 'namespace', 'cert-manager', "--kubeconfig=$kubeconfig"]);
            $process->run();

            if(!$process->isSuccessful()){
                $errors[] = new ProcessFailedException($process);
            }

            // Installing the cert manager
            $process = new Process(["helm","install","cert-manager","--namespace=cert-manager","--version=v0.15.0","jetstack/cert-manager","--set","installCRDs=true","--kubeconfig=$kubeconfig"]);
            $process->run();

            if($process->isSuccessful()){
                $configurations[] = 'cert-manager';
            }
            else{
                $errors[] = new ProcessFailedException($process);
            }

            echo "Give Cert Manager some time\n";
            sleep(20);
        }

        if(in_array('cert-manager', $configurations) &&!in_array('cert-issuer', $configurations)){
            echo "Install the general cluster cert issuer";
            $process = new Process(['kubectl', 'create', '-f','https://raw.githubusercontent.com/ConductionNL/environment-component/master/resources/cert-issuer.yaml', "--kubeconfig=$kubeconfig", "--namespace=default"]);
            $process->run();

            if($process->isSuccessful()){
                $configurations[] = 'cert-issuer';
            }
            else{
                $errors[] = new ProcessFailedException($process);
            }
        }
        $cluster->setStatus('running');
        $cluster->setConfigurations($configurations);

        if(count($errors)>0)
        {
            foreach($errors as $error){
                echo $error->getMessage();
            }
        }
        else
        {
            $now = New \DateTime();
            $cluster->setDateConfigured($now);
        }
        $this->removeKubeconfig($kubeconfig);
        return $cluster;
    }

    public function createNamespace(Environment $environment): bool
    {
        $kubeconfig = $this->writeKubeconfig($environment->getCluster());

        $process = new Process(['kubectl', 'create', 'namespace', "{$environment->getName()}", "--kubeconfig={$kubeconfig}"]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->isSuccessful();
    }

    public function getHelmVersion()
    {
        $process = new Process(['helm', 'version']);
        $process->run();

        return $process->getOutput();
    }

    public function getNamespaces(Cluster $cluster)
    {
        $kubeconfig = $this->writeKubeconfig($cluster);

        $process = new Process(['kubectl', 'get', 'namespaces', "--kubeconfig={$kubeconfig}"]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);
        $namespaces = [];
        $iterator = 0;
        foreach (explode("\n", $process->getOutput()) as $namespace) {
            if ($iterator > 0) {
                array_push($namespaces, explode(' ', $namespace)[0]);
            }
            $iterator++;
        }

        return $namespaces;
    }

    public function getReleases(Cluster $cluster)
    {
        $kubeconfig = $this->writeKubeconfig($cluster);

        $process = new Process(['helm', 'ls', '--all-namespaces', "--kubeconfig={$kubeconfig}"]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);
        $releases = [];
        $iterator = 0;
        foreach (explode("\n", $process->getOutput()) as $release) {
            if ($iterator > 0) {
                array_push($releases, explode(' ', $release)[0]);
            }
            $iterator++;
        }

        return $releases;
    }

    public function addRepo(Installation $installation)
    {
        $process = new Process(['rm', '-rf', '~/.helm/cache/archive/*', '&&', 'rm', '-rf', '~/.helm/repository/cache/*']);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        //Add repo to repos
        $process = new Process(['helm', 'repo', 'add', "{$installation->getComponent()->getCode()}-repository", "{$installation->getComponent()->getHelmRepository()}"]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $process = new Process(['helm', 'repo', 'update']);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function installComponent(Installation $installation): bool
    {
        $kubeconfig = $this->writeKubeconfig($installation->getEnvironment()->getCluster());
        $additionalSettings = '';
        foreach ($installation->getProperties() as $property) {
            $additionalSettings .= ",{$property->getName()}={$property->getValue()}";
        }
        $this->addRepo($installation);

        //Install
        $process = new Process([
            'helm',
            'install',
            "{$installation->getDeploymentName()}",
            "{$installation->getComponent()->getCode()}-repository/{$installation->getComponent()->getCode()}",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
            '--set', "settings.env={$installation->getEnvironment()->getName()},settings.debug={$installation->getEnvironment()->getDebug()},settings.domain={$installation->getDomain()->getName()},settings.cache={$installation->getEnvironment()->getCache()},security.commongroundKey={$installation->getEnvironment()->getAuthorization()},security.applicationKey={$installation->getEnvironment()->getAuthorization()},security.authorisationProviderUser=https://uc.{$installation->getDomain()->getName()},security.authorisationProviderApplication=https://uc.{$installation->getDomain()->getName()},postgresql.enabled=false,postgresql.url={$installation->getDbUrl()}$additionalSettings",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);

        return $process->isSuccessful();
    }
    public function getStatus(Cluster $cluster){
        //TODO: make this dynamic based on provider


    }
    public function upgradeComponent(Installation $installation): bool
    {
        $additionalSettings = '';
        foreach ($installation->getProperties() as $property) {
            $additionalSettings .= ",{$property->getName()}={$property->getValue()}";
        }
        $kubeconfig = $this->writeKubeconfig($installation->getEnvironment()->getCluster());
        $this->addRepo($installation);

        //Install
        $process = new Process([
            'helm',
            'upgrade',
            "{$installation->getDeploymentName()}",
            "{$installation->getComponent()->getCode()}-repository/{$installation->getComponent()->getCode()}",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
            '--set', "settings.env={$installation->getEnvironment()->getName()},settings.debug={$installation->getEnvironment()->getDebug()},settings.domain={$installation->getDomain()->getName()},settings.cache={$installation->getEnvironment()->getCache()},security.commongroundKey={$installation->getEnvironment()->getAuthorization()},security.applicationKey={$installation->getEnvironment()->getAuthorization()},security.authorisationProviderUser=https://uc.{$installation->getDomain()->getName()},security.authorisationProviderApplication=https://uc.{$installation->getDomain()->getName()},postgresql.enabled=false,postgresql.url={$installation->getDbUrl()}$additionalSettings",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);

        return $process->isSuccessful();
    }

    public function deleteComponent(Installation $installation): bool
    {
        $kubeconfig = $this->writeKubeconfig($installation->getEnvironment()->getCluster());

        //Install
        $process = new Process([
            'helm',
            'delete',
            "{$installation->getDeploymentName()}",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        sleep(25);
        if ($installation->hasDeploymentName()) {
            $name = "{$installation->getDeploymentName()}-{$installation->getEnvironment()->getName()}-{$installation->getDeploymentName()}";
        } else {
            $name = "{$installation->getComponent()->getCode()}-{$installation->getEnvironment()->getName()}-{$installation->getComponent()->getCode()}";
        }
        $process = new Process([
            'kubectl',
            'delete',
            'secret',
            "$name",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);

        return $process->isSuccessful();
    }

    public function restartComponent(Installation $installation): bool
    {
        $kubeconfig = $this->writeKubeconfig($installation->getEnvironment()->getCluster());

        $this->addRepo($installation);

        //Rolling Update
        $process = new Process([
            'kubectl',
            'rollout',
            'restart',
            "deployment/{$installation->getComponent()->getCode()}-php",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $process = new Process([
            'kubectl',
            'rollout',
            'restart',
            "deployment/{$installation->getComponent()->getCode()}-nginx",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $process = new Process([
            'kubectl',
            'rollout',
            'restart',
            "deployment/{$installation->getComponent()->getCode()}-varnish",
            "--namespace={$installation->getEnvironment()->getName()}",
            "--kubeconfig={$kubeconfig}",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);

        return $process->isSuccessful();
    }
}
