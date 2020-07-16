<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Component;
use App\Entity\Domain;
use App\Service\CloudFlareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class DnsSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;
    private $cloudFlareService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, CloudFlareService $cloudFlareService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->cloudFlareService = $cloudFlareService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['dns', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function dns(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $domain = $event->getControllerResult();

        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }
//        var_dump($route);
        // We should also check on entity = component
        if ($method != 'GET' || !strpos($route, '_dns_setup') && !strpos($route, '_dns_clear')) {
//            var_dump($route);
            return;
        }
//        var_dump($route);
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
        if ($domain instanceof Domain) {
            if (!strpos($route, '_dns_setup')) {
                $domain = $this->cloudFlareService->removeDNSRecordsForDomain($domain);
            } else {
                $domain = $this->cloudFlareService->createDNSRecordsForDomain($domain);
            }
            $this->em->persist($domain);
            $this->em->flush();
        }
        $response = $this->serializer->serialize(
            $domain,
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
