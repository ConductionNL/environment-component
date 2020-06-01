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

    public function configureCluster(Cluster $cluster)
    {
        $kubeconfig = $this->writeKubeconfig($cluster);

        echo "Installing kubernetes dashboard\n";
        $process4 = new Process(['kubectl', 'create', '-f https://raw.githubusercontent.com/kubernetes/dashboard/v2.0.0/aio/deploy/recommended.yaml', "--kubeconfig={$kubeconfig}"]);
        $process4->run();
        if (!$process4->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process4);
        }

        echo "Installing Ingress\n";
        $process5 = new Process(['helm', 'install', 'loadbalancer', 'stable/nginx-ingress', "--kubeconfig=$kubeconfig"]);
        $process5->run();
        if (!$process5->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process5);
        }

        $process6 = new Process(['helm', 'upgrade', 'loadbalancer', 'stable/nginx-ingress', "--kubeconfig=$kubeconfig"]);
        $process6->run();
        if (!$process6->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process6);
        }

        $process9 = new Process(['helm', 'repo', 'add', 'jetstack', 'https://charts.jetstack.io']);
        $process9->run();
        if (!$process9->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process9);
        }

        // Creating the name space for the cert manager
        $process11 = new Process(['kubectl', 'create', 'namespace', 'cert-manager', "--kubeconfig=$kubeconfig"]);
        $process11->run();
        if (!$process11->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process11);
        }

        // Installing the cert manager
        $process10 = new Process(['helm', 'install', 'cert-manager', '--namespace=cert-manager', '--version=v0.15.0', 'jetstack/cert-manager', '--set installCRDs=true', "--kubeconfig=$kubeconfig"]);
        $process10->run();
        if (!$process10->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process10);
        }

        // Installing the general cluster cert issuer
        $process11 = new Process(['kubectl', 'create', '-f https://raw.githubusercontent.com/ConductionNL/environment-component/resources/cert-issuer.yaml', "--kubeconfig=$kubeconfig"]);
        $process11->run();
        if (!$process11->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process10);
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

        //Add repo to repos
        $process = new Process(['helm', 'repo', 'remove', "{$installation->getComponent()->getCode()}-repository"]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $process = new Process(['helm', 'repo', 'add', "{$installation->getComponent()->getCode()}-repository", "{$installation->getComponent()->getHelmRepository()}"]);
        $process->run();
        $process = new Process(['rm', '-rf', '~/.helm/cache/archive/*', '&&', 'rm', '-rf', '~/.helm/repository/cache/*']);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
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
            '--set', "settings.env={$installation->getEnvironment()->getName()},settings.debug={$installation->getEnvironment()->getDebug()},settings.domain={$installation->getDomain()->getName()},settings.trustedHosts=^(.+\.)".addcslashes($installation->getDomain()->getName(), '.')."$,settings.cache={$installation->getEnvironment()->getCache()},security.commongroundKey={$installation->getEnvironment()->getAuthorization()},security.applicationKey={$installation->getEnvironment()->getAuthorization()},security.authorisationProviderUser=https://uc.{$installation->getDomain()->getName()},security.authorisationProviderApplication=https://uc.{$installation->getDomain()->getName()},postgresql.enabled=false,postgresql.url={$installation->getDbUrl()}$additionalSettings",
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->removeKubeconfig($kubeconfig);

            throw new ProcessFailedException($process);
        }
        $this->removeKubeconfig($kubeconfig);

        return $process->isSuccessful();
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
            '--set', "settings.env={$installation->getEnvironment()->getName()},settings.debug={$installation->getEnvironment()->getDebug()},settings.domain={$installation->getDomain()->getName()},settings.trustedHosts=^(.+\.)".addcslashes($installation->getDomain()->getName(), '.')."$,settings.cache={$installation->getEnvironment()->getCache()},security.commongroundKey={$installation->getEnvironment()->getAuthorization()},security.applicationKey={$installation->getEnvironment()->getAuthorization()},security.authorisationProviderUser=https://uc.{$installation->getDomain()->getName()},security.authorisationProviderApplication=https://uc.{$installation->getDomain()->getName()},postgresql.enabled=false,postgresql.url={$installation->getDbUrl()}$additionalSettings",
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
        if ($installation->hasDeploymentName()) {
            $name = "{$installation->getDeploymentName()}-{$installation->getEnvironment()->getName()}-cert";
        } else {
            $name = "{$installation->getComponent()->getCode()}-{$installation->getEnvironment()->getName()}-cert";
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
