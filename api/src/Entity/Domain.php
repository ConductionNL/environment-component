<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     	normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     	denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 * 		"get",
 * 	    "put",
 * 	   "delete",
 *     "get_change_logs"={
 *              "path"="/domains/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *     "get_audit_trail"={
 *              "path"="/domains/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          }
 * 		},
 * )
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 * @Gedmo\Loggable(logEntryClass="App\Entity\ChangeLog")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
 *
 */
class Domain
{
    /**
     * @var UuidInterface The UUID identifier of this resource
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Uuid
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @var string The name of this domain
     *
     * @example conduction.nl
     *
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string the description of this domain
     *
     * @example This domain contains the conduction website
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string the base route of this domain
     *
     * @example https://conduction.nl
     *
     * @Assert\Url
     * @Assert\Length(
     *     max=255
     * )
     * @Assert\NotNull
     * @ORM\Column(type="string", length=255)
     */
    private $location;

    /**
     * @var string the IP Address of this domain
     * @TODO: maybe this should not be here, as clusters also contain ip addresses
     *
     * @Groups({"read","write"})
     * @example 255.255.255.0
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ip;

    /**
     * @var string the base url for the managed database that this domain uses
     *
     * @example pgsql://db-cluster.vuga.com:25060/
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $databaseUrl;

    /**
     * @var Datetime The moment this entity was created
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateCreated;

    /**
     * @var Datetime The moment this entity last Modified
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateModified;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="App\Entity\Cluster", inversedBy="domains")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cluster;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\ManyToMany(targetEntity="App\Entity\Component", mappedBy="domains")
     */
    private $components;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\OneToMany(targetEntity="App\Entity\HealthLog", mappedBy="domain", orphanRemoval=true)
     */
    private $healthLogs;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Environment", inversedBy="domain")
     * @ORM\JoinColumn(nullable=false)
     */
    private $environment;

    public function __construct()
    {
        $this->components = new ArrayCollection();
        $this->healthLogs = new ArrayCollection();
        $this->components1 = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function getEnvironment(): ?Environment
    {
        return $this->environment;
    }

    public function setEnvironment(?Environment $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @return Collection|Component[]
     */
    public function getComponents1(): Collection
    {
        return $this->components1;
    }

    public function addComponents1(Component $components1): self
    {
        if (!$this->components1->contains($components1)) {
            $this->components1[] = $components1;
            $components1->setDomain($this);
        }

        return $this;
    }

    public function removeComponents1(Component $components1): self
    {
        if ($this->components1->contains($components1)) {
            $this->components1->removeElement($components1);
            // set the owning side to null (unless already changed)
            if ($components1->getDomain() === $this) {
                $components1->setDomain(null);
            }
        }

        return $this;
    }
}
