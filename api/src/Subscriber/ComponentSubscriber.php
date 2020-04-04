<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\AuditTrail;
use App\Entity\Component;
use App\Service\InstallService;
use App\Service\NLXLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class ComponentSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;
    private $installService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, NLXLogService $nlxLogService, InstallService $installService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->nlxLogService = $nlxLogService;
        $this->installService = $installService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['Audittrail', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function Audittrail(GetResponseForControllerResultEvent $event)
    {
        return;
//        $method = $event->getRequest()->getMethod();
//        $route = $event->getRequest()->attributes->get('_route');
//        $result = $event->getControllerResult();
//
//        // Only do somthing if we are on te log route and the entity is logable
//        if ($method != 'GET' || !($result instanceof Component) || !strpos($route, '_install_item') && !strpos($route, '_update_item')) {
//            return;
//        }
//
//        // Lets get the rest of the data
//        $contentType = $event->getRequest()->headers->get('accept');
//        if (!$contentType) {
//            $contentType = $event->getRequest()->headers->get('Accept');
//        }
//        switch ($contentType) {
//            case 'application/json':
//                $renderType = 'json';
//                break;
//            case 'application/ld+json':
//                $renderType = 'jsonld';
//                break;
//            case 'application/hal+json':
//                $renderType = 'jsonhal';
//                break;
//            default:
//                $contentType = 'application/json';
//                $renderType = 'json';
//        }
//
//        if(strpos($route, '_install_item')){
//            $response = $this->installService->install($result);
//        }else{
//            $response = $this->installService->update($result);
//        }
//        $result['message'] = $response;
//
//
//        $response = $this->serializer->serialize(
//            $result,
//            $renderType,
//            ['enable_max_depth'=> true]
//        );
//
//        // Creating a response
//        $response = new Response(
//            $response,
//            Response::HTTP_CREATED,
//            ['content-type' => $contentType]
//        );
//
//        $event->setResponse($response);
    }
}
