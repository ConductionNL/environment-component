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
 *     "helm_install"={
 *              "path"="/installations/{id}/install",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="install",
 *                  "description"="Installs this component to a cluster"
 *              }
 *     },
 *     "helm_delete"={
 *              "path"="/installations/{id}/delete",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="delete",
 *                  "description"="Deletes this component from a cluster"
 *              }
 *     },
 *     "helm_update"={
 *              "path"="/installations/{id}/update",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="update",
 *                  "description"="Updates this component on a cluster"
 *              }
 *     },
 *     "get_change_logs"={
 *              "path"="/installations/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *     "get_audit_trail"={
 *              "path"="/installations/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          }
 * 		},
 * )
 * @ORM\Entity(repositoryClass="App\Repository\InstallationRepository")
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class, properties={"environment.cluster.id": "exact", "component.id": "exact"})
 */
class Installation
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
     * @var string The name of this component
     *
     * @example evc
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
     * @var string the description of this component
     *
     * @example This common ground component describes common ground components
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string The username that is needed to log into the cluster database
     *
     * @example evc-dev
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbUsername;

    /**
     * @var string The password that is needed to log into the cluster database
     *
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbPassword;

    /**
     * @var string The name of the database this component uses
     *
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbName;

    /**
     * @var string The authentication token that is needed to access this token
     *
     * @example evc-dev
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true, name="authorization_key"))
     */
    private $authorization;

    /**
     * @var string the Github Repository that contains this component
     *
     * @example https://github.com/ConductionNL/environment-component
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbUrl;

    /**
     * @var string the Github Repository that contains this component
     *
     * @example https://github.com/ConductionNL/environment-component
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private $helmVersion;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="App\Entity\Component", inversedBy="installations")
     */
    private $component;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="App\Entity\Domain", inversedBy="installations")
     */
    private $domain;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="App\Entity\Environment", inversedBy="installations")
     */
    private $environment;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\OneToMany(targetEntity="App\Entity\HealthLog", mappedBy="installation")
     */
    private $healthLogs;

    /**
     * @var Datetime The moment this entity was last installed
     *
     * @Groups({"read"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateInstalled;

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
     * @var Property additional properties that are required for this installation, i.e. external API keys
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\OneToMany(targetEntity=Property::class, mappedBy="installation", cascade="persist")
     */
    private $properties;

    public function __construct()
    {
        $this->healthLogs = new ArrayCollection();
        $this->properties = new ArrayCollection();
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

    public function getEnvironment(): ?Environment
    {
        return $this->environment;
    }

    public function setEnvironment(Environment $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    public function getDbUsername(): ?string
    {
        return $this->dbUsername;
    }

    public function setDbUsername(string $dbUsername): self
    {
        $this->dbUsername = $dbUsername;

        return $this;
    }

    public function getDbPassword(): ?string
    {
        return $this->dbPassword;
    }

    public function setDbPassword(string $dbPassword): self
    {
        $this->dbPassword = $dbPassword;

        return $this;
    }

    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }

    public function setAuthorization(string $authorization): self
    {
        $this->authorization = $authorization;

        return $this;
    }

    public function getDateInstalled(): ?\DateTimeInterface
    {
        return $this->dateInstalled;
    }

    public function setDateInstalled(?\DateTimeInterface $dateInstalled): self
    {
        $this->dateInstalled = $dateInstalled;

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
            $healthLog->setComponent($this);
        }

        return $this;
    }

    public function removeHealthLog(HealthLog $healthLog): self
    {
        if ($this->healthLogs->contains($healthLog)) {
            $this->healthLogs->removeElement($healthLog);
            // set the owning side to null (unless already changed)
            if ($healthLog->getComponent() === $this) {
                $healthLog->setComponent(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): self
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function getDbUrl(): ?string
    {
        return $this->dbUrl;
    }

    public function setDbUrl(string $dbUrl): self
    {
        $this->dbUrl = $dbUrl;

        return $this;
    }

    public function getHelmVersion(): ?string
    {
        return $this->helmVersion;
    }

    public function setHelmVersion(string $helmVersion): self
    {
        $this->helmVersion = $helmVersion;

        return $this;
    }

    public function getComponent(): ?Component
    {
        return $this->component;
    }

    public function setComponent(?Component $component): self
    {
        $this->component = $component;
        $component->addInstallation($this);
        return $this;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;
        $domain->addInstallation($this);
        return $this;
    }

    /**
     * @return Collection|Property[]
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): self
    {
        if (!$this->properties->contains($property)) {
            $this->properties[] = $property;
            $property->setInstallation($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): self
    {
        if ($this->properties->contains($property)) {
            $this->properties->removeElement($property);
            // set the owning side to null (unless already changed)
            if ($property->getInstallation() === $this) {
                $property->setInstallation(null);
            }
        }

        return $this;
    }
}
