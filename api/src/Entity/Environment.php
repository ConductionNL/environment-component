<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
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
 *              "path"="/environments/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *     "get_audit_trail"={
 *              "path"="/environments/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          },
 *     "helm_update"={
 *              "path"="/environments/{id}/update",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="update",
 *                  "description"="Performs a rolling update on all installations in this environment"
 *              }
 *          }
 * 		},
 *
 * )
 * @ORM\Entity(repositoryClass="App\Repository\EnvironmentRepository")
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class, properties={"cluster.id": "exact"})
 */
class Environment
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
     * @var string Should components be deployed to this environment with debuggin on or off?
     *
     * @example 1
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @Assert\NotNull
     * @Assert\Choice({0, 1})
     * @ORM\Column(type="integer")
     */
    private $debug = 0;

    /**
     * @var string Should components be deployed to this environment with debuggin on or off?
     *
     * @example 1
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @Assert\NotNull
     * @Assert\Choice({0, 1})
     * @ORM\Column(type="integer")
     */
    private $web = 1;

    /**
     * @var string The authentication token that is needed to access this token
     *
     * @example evc-dev
     *
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true, name="authorization_key")
     */
    private $authorization;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="App\Entity\Cluster", inversedBy="environments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cluster;

    /**
     * @var ArrayCollection The installations in this environment
     *
     * @Groups({"read","write"})
     * @MaxDepth(1)
     * @ORM\OneToMany(targetEntity="App\Entity\Installation", mappedBy="environment")
     */
    private $installations;

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
     * @var int Should components be deployed to this environment with caching on or off?
     *
     * @example 1
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @Assert\NotNull
     * @ORM\Column(type="integer")
     */
    private $cache;

    public function __construct()
    {
        $this->installations = new ArrayCollection();
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

    public function getDebug(): ?int
    {
        return $this->debug;
    }

    public function setDebug(int $debug): self
    {
        $this->debug = $debug;

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

    /**
     * @return Collection|Installation[]
     */
    public function getInstallations(): Collection
    {
        return $this->installations;
    }

    public function addInstallation(Installation $installation): self
    {
        if (!$this->installations->contains($installation)) {
            $this->installations[] = $installation;
            $installation->setEnviroment($this);
        }

        return $this;
    }

    public function removeInstallation(Installation $installation): self
    {
        if ($this->installations->contains($installation)) {
            $this->installations->removeElement($installation);
            // set the owning side to null (unless already changed)
            if ($installation->getEnviroment() === $this) {
                $installation->setEnviroment(null);
            }
        }

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

    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }

    public function setAuthorization(?string $authorization): self
    {
        $this->authorization = $authorization;

        return $this;
    }

    public function getCache(): ?int
    {
        return $this->cache;
    }

    public function setCache(int $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function getWeb(): ?int
    {
        return $this->web;
    }

    public function setWeb(?int $web): self
    {
        $this->web = $web;

        return $this;
    }
}
