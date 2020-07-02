<?php


namespace App\Service;


use App\Entity\Installation;
use Doctrine\ORM\EntityManagerInterface;

class CysoDatabaseServer
{
    private $dbUrl;
    private $dbUser;
    private $dbPassword;
    private $dbPort;
    private $manager;

    public function __construct(string $dbUrl, string $dbUser, string $dbPassword, int $dbPort, EntityManagerInterface $manager){
        $this->dbUrl = $dbUrl;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->manager = $manager;
        $this->dbPort = $dbPort;
    }
    public function createDatabase(Installation $installation)
    {
        $dbName = "{$installation->getComponent()->getCode()}-{$installation->getEnvironment()->getName()}";
        $password = hash('md5', random_bytes(10));

        $sql = '
            create user "'.$dbName.'" with encrypted password '.$password.';
            create database '.$dbName.' with owner '.$dbName.';
        ';
        $stmt = $this->manager->getConnection()->prepare($sql);
        $stmt->execute();
        $installation->setDbUrl("pgsql://$dbName:$password@{$this->dbUrl}:{$this->dbPort}/$dbName?sslmode=require&serverVersion=10")
    }

}
