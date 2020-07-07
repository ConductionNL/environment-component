<?php

namespace App\Service;

use App\Entity\Installation;
use App\Entity\HealthLog;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\BadResponseException;
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
            // Allow redirect
            'allow_redirects' => false,
            // Base URI is used with relative requests
            'http_errors' => false,
            //'base_uri' => 'https://wrc.zaakonline.nl/applications/536bfb73-63a5-4719-b535-d835607b88b2/',
            // You can set any number of default request options.
            'timeout'  => 200.0,
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
        $health->setDomain($installation->getDomain());
        $health->setCode(200);
        $health->setStatus('ok');

        // lets get the name
        if($installation->getDeploymentName() && $installation->getDeploymentName() != '')
        {
            $name = $installation->getDeploymentName();
        }
        else{
            $name = $installation->getComponent()->getCode();
        }

        // let establisch the domain
        $domain = $installation->getDomain()->getName();

        // lets detirmine a path for our healt check
        if($installation->getEnvironment()->getName()== 'prod'){
            $url = 'https://'.$name.'.'.$domain;
        }
        else{
            $url = 'https://'.$name.'.'.$installation->getEnvironment()->getName().'.'.$domain;
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

        $this->em->persist($health);
        $this->em->flush();

        return $health;
    }



}
