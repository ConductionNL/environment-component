<?php

namespace App\Subscriber;

use App\Entity\Cluster;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Process\Process;

class K8ClusterDoctrineSubscriber implements EventSubscriber
{

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->cluster('persist', $args);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->cluster('remove', $args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->cluster('update', $args);
    }

    public function cluster(string $action, LifecycleEventArgs $args)
    {
        $cluster = $args->getObject();

        if (!$cluster instanceof Cluster) {
            return;
        }

        if($action == 'remove'){
            $process = new Process(['bin/console', 'app:k8cluster:delete', $cluster->getId()]);
            $process->start();
        }
        elseif($action == 'persist'){
            $process = new Process(['bin/console', 'app:k8cluster:create', $cluster->getId()]);
            $process->start();
        }
    }
}
