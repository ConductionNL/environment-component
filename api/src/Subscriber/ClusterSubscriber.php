<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Cluster;
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

class ClusterSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['Cluster', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function Cluster(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $result = $event->getControllerResult();

        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }
        // We should also check on entity = result
        if ($method != 'GET' || !($result instanceof Cluster)) {
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

        $result = $this->digitalOceanService->createKubeConfig($result);

        $releases = $this->clusterService->getReleases($result);
        $result->setReleases($releases);

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
