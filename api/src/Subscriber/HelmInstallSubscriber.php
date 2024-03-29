<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Component;
use App\Service\InstallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class HelmInstallSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;
    private $installService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, InstallService $installService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->installService = $installService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['HelmInstall', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function HelmInstall(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $component = $event->getControllerResult();

        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }

        // We should also check on entity = component
        if ($method != 'GET' || !strpos($route, '_helm_install')) {
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

        $results = $this->installService->install($component);
        $component->setDateInstalled(new \DateTime('now'));

        $this->em->persist($component);
        $this->em->flush();
        //$component['message'] = $results;
        $response = $this->serializer->serialize(
            $component,
            'json',
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
