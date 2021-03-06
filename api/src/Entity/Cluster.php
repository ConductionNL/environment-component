<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This entity holds the information about a kubernetes cluster.
 *
 * @ApiResource(
 *     	normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     	denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 * 		"get",
 * 	    "put",
 * 	   "delete",
 *     "get_change_logs"={
 *              "path"="/clusters/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *     "get_audit_trail"={
 *              "path"="/clusters/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          }
 * 		},
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ClusterRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class, properties={"id": "exact"})
 */
class Cluster
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
     * @var string The name of this cluster
     *
     * @example conduction cluster
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
     * @var string The status of this cluster
     *
     * @example running
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private $status = 'requested';
    /**
     * @var string The cloud provider where the cluster should be
     *
     * @example running
     *
     * @Gedmo\Versioned
     * @Assert\Choice(
     * {
     *     "CYSO",
     *     "Digital Ocean"
     * }
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $provider;

    /**
     * @var array The standard applications that have been installed
     *
     * @Gedmo\Versioned
     * @Groups({"read"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $configurations = [];
    /**
     * @var string The id of this cluster with its provide e.g. digital ocean
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private $providerId = '';

    /**
     * @var string the description of this cluster
     *
     * @example This cluster is for conduction's own systems
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string the kubernetes configuration file of this cluster
     *
     * @Gedmo\Versioned
     * @Groups({"write"})
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $kubeconfig;

    /**
     * @var string the IP Address of this cluster
     *
     * @Groups({"read","write"})
     *
     * @example 255.255.255.0
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ip;

    /**
     * @var string the Tiller token of this cluster
     *
     *
     * @Groups({"write"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $token;

    /**
     * @Groups({"read","write"})
     * @MaxDepth(1)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Domain", mappedBy="cluster")
     */
    private $domains;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Environment", mappedBy="cluster")
     * @ApiSubresource()
     */
    private $environments;

    /**
     * @var int The amount of installations container on this cluster
     *
     * @example 1
     *
     * @Groups({"read"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $installations = 0;

    /**
     * @var int The amount of installations container on this cluster that are healthy
     *
     * @example 1
     *
     * @Groups({"read"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $health = 0;

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
     * @var Datetime The moment this cluster was configured
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateConfigured;

    /**
     * @var array Installed releases on this cluster
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $releases = [];

    public function __construct()
    {
        $this->domains = new ArrayCollection();
        $this->environments = new ArrayCollection();
    }

    public function getHealth(): ?int
    {
        return $this->health;
    }

    public function setHealth(int $health): self
    {
        $this->health = $health;

        return $this;
    }

    public function getInstallations(): ?int
    {
        return $this->installations;
    }

    public function setInstallations(int $installations): self
    {
        $this->installations = $installations;

        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(string $providerId): self
    {
        $this->providerId = $providerId;

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

    public function getKubeconfig(): ?string
    {
        return $this->kubeconfig;
    }

    public function setKubeconfig(string $kubeconfig): self
    {
        $this->kubeconfig = $kubeconfig;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection|Domain[]
     */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): self
    {
        if (!$this->domains->contains($domain)) {
            $this->domains[] = $domain;
            $domain->setCluster($this);
        }

        return $this;
    }

    public function removeDomain(Domain $domain): self
    {
        if ($this->domains->contains($domain)) {
            $this->domains->removeElement($domain);
            // set the owning side to null (unless already changed)
            if ($domain->getCluster() === $this) {
                $domain->setCluster(null);
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

    public function getDateConfigured(): ?\DateTimeInterface
    {
        return $this->dateConfigured;
    }

    public function setDateConfigured(?\DateTimeInterface $dateConfigured): self
    {
        $this->dateConfigured = $dateConfigured;

        return $this;
    }

    /**
     * @return Collection|Environment[]
     */
    public function getEnvironments(): Collection
    {
        return $this->environments;
    }

    public function addEnvironment(Environment $environment): self
    {
        if (!$this->environments->contains($environment)) {
            $this->environments[] = $environment;
            $environment->setCluster($this);
        }

        return $this;
    }

    public function removeEnvironment(Environment $environment): self
    {
        if ($this->environments->contains($environment)) {
            $this->environments->removeElement($environment);
            // set the owning side to null (unless already changed)
            if ($environment->getCluster() === $this) {
                $environment->setCluster(null);
            }
        }

        return $this;
    }

    public function hasEnvironment(string $name)
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('name', $name));

        return count($this->getEnvironments()->matching($criteria)) > 0;
    }

    public function getReleases(): ?array
    {
        return $this->releases;
    }

    public function setReleases(?array $releases): self
    {
        $this->releases = $releases;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getConfigurations(): ?array
    {
        return $this->configurations;
    }

    public function setConfigurations(?array $configurations): self
    {
        $this->configurations = $configurations;

        return $this;
    }
}
