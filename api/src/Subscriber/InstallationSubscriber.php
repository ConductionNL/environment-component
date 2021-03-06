<?php

namespace App\Subscriber;

use App\Entity\Installation;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class InstallationSubscriber implements EventSubscriber
{
    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::preRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->installation('persist', $args);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->installation('remove', $args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->installation('update', $args);
    }

    public function installation(string $action, LifecycleEventArgs $args)
    {
        $installation = $args->getObject();

        if (!$installation instanceof Installation) {
            return;
        }

        if ($action == 'remove') {
            $process = new Process(['../bin/console', 'app:component:delete', $installation->getId()]);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
//        else{
//            $process = new Process(['../bin/console', 'app:component:update', $installation->getId()]);
//            $process->start();
//        }
    }
}
