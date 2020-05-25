<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Cluster;
use App\Entity\Component;
use App\Entity\Environment;
use App\Entity\Installation;
use App\Service\ClusterService;
use App\Service\DigitalOceanService;
use App\Service\InstallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{

    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;
    private $clusterService;
    private $digitalOceanService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, ClusterService $clusterService, DigitalOceanService $digitalOceanService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->clusterService = $clusterService;
        $this->digitalOceanService = $digitalOceanService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['Environment', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function Environment(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $result = $event->getControllerResult();

        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }
        // We should also check on entity = result
        if ($method != 'POST' || (!($result instanceof Environment) && !($result instanceof Component))) {
            return;
        }


        switch ($contentType) {
            case 'application/json':
                $renderType = 'json';
                break;
            case 'application/ld+json':
                $renderType = 'jsonld';
                break;
            case 'application/hal+json':
                $renderType = 'jsonhal';
                break;
            default:
                $contentType = 'application/json';
                $renderType = 'json';
        }

        if($result instanceof Environment){
            $components = $this->em->getRepository('App:Component')->findAll();
            foreach($components as $component){

                if($component instanceof Component && $component->getCore()){
                    $installation = new Installation();
                    $installation->setName("{$component->getCode()}-{$result->getName()}");
                    $installation->setAuthorization($result->getAuthorization());
                    $installation->setComponent($component);
                    $installation->setDomain($result->getCluster()->getDomains()[0]);
                    $installation->setEnvironment($result);
                    $installation->setHelmVersion("v3.2.1");
                    $this->em->persist($installation);
                }
                $this->em->flush();
            }
        }
        if($result instanceof Component){
            $environments = $this->em->getRepository('App:Environment')->findAll();
            foreach($environments as $environment){

                if($environment instanceof Environment && $result->getCore()){
                    $installation = new Installation();
                    $installation->setName("{$result->getCode()}-{$environment->getName()}");
                    $installation->setAuthorization($environment->getAuthorization());
                    $installation->setComponent($result);
                    $installation->setDomain($environment->getCluster()->getDomains()[0]);
                    $installation->setEnvironment($environment);
                    $installation->setHelmVersion("v3.2.1");
                    $this->em->persist($installation);
                }
                $this->em->flush();
            }
        }

        $response = $this->serializer->serialize(
            $result,
            $renderType,
            ['enable_max_depth'=> true]
        );

        // Creating a response

        $response = new Response(
            $response,
            Response::HTTP_OK,
            ['content-type' => $contentType]
        );

        $event->setResponse($response);
    }
}
