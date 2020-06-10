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

        echo "Installing kubernetes dashboard\n";
        $process1 = new Process(['kubectl', 'create', '-f', 'https://raw.githubusercontent.com/kubernetes/dashboard/v2.0.0/aio/deploy/recommended.yaml', "--kubeconfig={$kubeconfig}"]);
        $process1->run();

        echo "Installing Ingress\n";
        $process2 = new Process(['helm', 'repo', 'add', 'stable', 'https://kubernetes-charts.storage.googleapis.com']);
        $process2->run();

        $process3 = new Process(['helm', 'install', 'loadbalancer', 'stable/nginx-ingress', "--kubeconfig=$kubeconfig"]);
        $process3->run();

        $process4 = new Process(['helm', 'upgrade', 'loadbalancer', 'stable/nginx-ingress', "--kubeconfig=$kubeconfig"]);
        $process4->run();

        echo "Installing Cert Manager\n";
        $process5 = new Process(['helm', 'repo', 'add', 'jetstack', 'https://charts.jetstack.io']);
        $process5->run();

        // Creating the name space for the cert manager
        $process6 = new Process(['kubectl', 'create', 'namespace', 'cert-manager', "--kubeconfig=$kubeconfig"]);
        $process6->run();

        // Installing the cert manager
        $process7 = new Process(['helm', 'install', 'cert-manager', '--namespace=cert-manager', '--version=v0.15.0', 'jetstack/cert-manager', '--set', 'installCRDs=true', "--kubeconfig=$kubeconfig"]);
        $process7->run();

        echo "Give Cert Manager some time\n";
        sleep(10);

        echo 'Install the general cluster cert issuer';
        // Installing the general cluster cert issuer
        $process8 = new Process(['kubectl', 'create', '-f', 'https://raw.githubusercontent.com/ConductionNL/environment-component/dev-ruben/resources/cert-issuer.yaml', "--kubeconfig=$kubeconfig"]);
        $process8->run();

        if (!$process1->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process1);
        }
        if (!$process2->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process2);
        }
        if (!$process3->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process3);
        }
        if (!$process4->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process4);
        }
        if (!$process5->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process5);
        }
        if (!$process6->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process6);
        }
        if (!$process7->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process7);
        }
        if (!$process8->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process8);
        }
        $this->removeKubeconfig($kubeconfig);

        $cluster->setStatus('running');

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

    public function getStatus(Cluster $cluster)
    {
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
