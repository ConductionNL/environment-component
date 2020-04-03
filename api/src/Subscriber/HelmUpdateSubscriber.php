<?php

namespace App\Subscriber;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Component;
use App\Service\InstallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class HelmUpdateSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, InstallService $InstallService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->installService = $installService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['HelmUpdate', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function HelmUpdate(GetResponseForControllerResultEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $result = $event->getControllerResult();
        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }

        // We should also check on entity = component
        if ($method != 'GET' || !strpos($route, '_helm_update')) {
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

        $results = $this->installService->;

        if (!$result instanceof Paginator) {
            $result['serviceID'] = $result->getid();
            $result['description'] = $this->em->getMetadataFactory()->getMetadataFor(get_class($result))->getName();
        }

        /*


        $results = $this->em->getRepository('App:AuditTrail')->findBy(['resource'=> $itemId,'resourceType'=> $entityType]);

        */
        $response = $this->serializer->serialize(
            $results,
            'json',
            ['enable_max_depth'=> true]
        );

        // Creating a response

        $response = new Response(
            $response,
            Response::HTTP_CREATED,
            ['content-type' => $contentType]
        );

        $event->setResponse($response);
    }
}