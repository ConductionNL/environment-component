<?php

namespace App\Service;

use App\Entity\HealthLog;
use App\Entity\Installation;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HealthService
{
    private $commonGroundService;
    private $em;
    private $params;

    public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService, EntityManagerInterface $em)
    {
        $this->commonGroundService = $commonGroundService;
        $this->em = $em;
        $this->params = $params;

        // guzzle stuff
        // To work with NLX we need a couple of default headers
        $this->headers = [
            'Accept'         => 'application/health+json',
            'Content-Type'   => 'application/json',
            // NL Api Strategie
            'Accept-Crs'   => 'EPSG:4326',
            'Content-Crs'  => 'EPSG:4326',
        ];

        // We might want to overwrite the guzle config, so we declare it as a separate array that we can then later adjust, merge or otherwise influence
        $this->guzzleConfig = [
            // Allow redirect
            'allow_redirects' => false,
            // Base URI is used with relative requests
            'http_errors' => false,
            //'base_uri' => 'https://wrc.zaakonline.nl/applications/536bfb73-63a5-4719-b535-d835607b88b2/',
            // Responce timeout in secondes
            'timeout'  => 10,
            // Connection timeout in secondes
            'connect_timeout' => 5,
            // To work with NLX we need a couple of default headers
            'headers' => $this->headers,
            // Do not check certificates
            'verify' => false,
        ];

        // Lets start up a default client
        $this->client = new Client($this->guzzleConfig);
    }

    public function check(Installation $installation)
    {
        // Make the special health guzzle call

        // save the result
        $health = new HealthLog();
        $health->setInstallation($installation);
        $health->setDomain($installation->getDomain());
        $health->setCode(200);
        $health->setStatus('ok');

        // lets get the name
        if ($installation->getDeploymentName() && $installation->getDeploymentName() != '') {
            $name = $installation->getDeploymentName();
        } else {
            $name = $installation->getComponent()->getCode();
        }

        // let establisch the domain
        $domain = $installation->getDomain()->getName();

        // Lets estabblis the propper subdomain
        $subdomain = $name.'.';

        // Lets scheck for overwrites
        // @todo make switch case
        foreach ($installation->getProperties() as $property) {
            if ($property->getName() == 'settings.subdomain') {
                $subdomain = $property->getValue();
                // The is the optional case of an empty sub domain, in wich case we dont want to add an dot
                if ($subdomain && $subdomain != '') {
                    $subdomain = $subdomain.'.';
                }
            }

            if ($property->getName() == 'settings.domain') {
                $domain = $property->getValue();
            }
        }

        // lets detirmine a path for our healt check
        if ($installation->getEnvironment()->getName() == 'prod') {
            $url = 'https://'.$subdomain.$domain;
        } else {
            $url = 'https://'.$subdomain.$installation->getEnvironment()->getName().'.'.$domain;
        }
        $health->setEndpoint($url);

        // Lets actually do a health check
        $headers = $this->headers;
        $headers['Authorization'] = $installation->getEnvironment()->getAuthorization();

        try {
            $response = $this->client->request('GET', $url, ['headers' => $headers, 'http_errors' => false]);
            $health->setCode($response->getStatusCode());
            $health->setStatus($response->getReasonPhrase());

        } catch (\Exception $e) {
            $health->setStatus($e->getMessage());
        }

        // Lets also set status text to the installation
        $installation = $health->getInstallation();
        $installation->setStatus($health->getStatus());

        $this->em->persist($installation);
        $this->em->persist($health);
        $this->em->flush();

        return $health;
    }
}
