<?php

namespace App\Service;

use App\Entity\Installation;
use App\Entity\HealthLog;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HealthService
{
    private $commonGroundService;
    private $em;
    private $params;

    public function __construct(ParameterBagInterface $params,  CommonGroundService $commonGroundService, EntityManagerInterface $em)
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
            // Base URI is used with relative requests
            'http_errors' => false,
            //'base_uri' => 'https://wrc.zaakonline.nl/applications/536bfb73-63a5-4719-b535-d835607b88b2/',
            // You can set any number of default request options.
            'timeout'  => 4000.0,
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
        $health = New HealthLog();
        $health->setInstallation($installation);
        $health->setDomain($installation);
        $health->setStatus('OK');
        $health->setCode(200);

        // lets get the name
        if($installation->getDeploymentName() && $installation->getDeploymentName() != '')
        {
            $name = $installation->getDeploymentName();
        }
        else{
            $name = $installation->getComponent()->getCode();
        }

        // let establisch the domain
        $domain = $installation->getDomain()->getLocation();

        // lets detirmine a path for our healt check
        if($installation->getEnvironment()->getName()== 'prod'){
            $url = 'https://'.$name.$domain;
        }
        else{
            $url = 'https://'.$name.$domain;
        }
        $health->setEndpoint($url);

        // Lets actually do a health check


        // Lets save the results
        $installation->setStatus($health->getStatus());

        $this->em->persist($health);
        $this->em->persist($installation);
        $this->em->flush();
    }



}
