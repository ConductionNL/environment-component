<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 */
class Domain
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ip;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateCreated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Cluster", inversedBy="domains")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cluster;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $databaseUrl;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Component", mappedBy="domains")
     */
    private $components;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\HealthLog", mappedBy="domain", orphanRemoval=true)
     */
    private $healthLogs;

    public function __construct()
    {
        $this->components = new ArrayCollection();
        $this->healthLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(?\DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getCluster(): ?Cluster
    {
        return $this->cluster;
    }

    public function setCluster(?Cluster $cluster): self
    {
        $this->cluster = $cluster;

        return $this;
    }

    public function getDatabaseUrl(): ?string
    {
        return $this->databaseUrl;
    }

    public function setDatabaseUrl(?string $databaseUrl): self
    {
        $this->databaseUrl = $databaseUrl;

        return $this;
    }

    /**
     * @return Collection|Component[]
     */
    public function getComponents(): Collection
    {
        return $this->components;
    }

    public function addComponent(Component $component): self
    {
        if (!$this->components->contains($component)) {
            $this->components[] = $component;
            $component->addDomain($this);
        }

        return $this;
    }

    public function removeComponent(Component $component): self
    {
        if ($this->components->contains($component)) {
            $this->components->removeElement($component);
            $component->removeDomain($this);
        }

        return $this;
    }

    /**
     * @return Collection|HealthLog[]
     */
    public function getHealthLogs(): Collection
    {
        return $this->healthLogs;
    }

    public function addHealthLog(HealthLog $healthLog): self
    {
        if (!$this->healthLogs->contains($healthLog)) {
            $this->healthLogs[] = $healthLog;
            $healthLog->setDomain($this);
        }

        return $this;
    }

    public function removeHealthLog(HealthLog $healthLog): self
    {
        if ($this->healthLogs->contains($healthLog)) {
            $this->healthLogs->removeElement($healthLog);
            // set the owning side to null (unless already changed)
            if ($healthLog->getDomain() === $this) {
                $healthLog->setDomain(null);
            }
        }

        return $this;
    }
}
