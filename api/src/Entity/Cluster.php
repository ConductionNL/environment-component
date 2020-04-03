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
 * This entity holds the information about a kubernetes cluster
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
 * @Gedmo\Loggable(logEntryClass="App\Entity\ChangeLog")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
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
     * @ORM\Column(type="text")
     */
    private $kubeconfig;

    /**
     * @var string the IP Address of this cluster
     *
     * @Groups({"read","write"})
     * @example 255.255.255.0
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ip;

    /**
     * @var string the Tiller token of this cluster
     *
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
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
     * @ORM\OneToMany(targetEntity="App\Entity\Environment", mappedBy="cluster")
     */
    private $environments;

    public function __construct()
    {
        $this->domains = new ArrayCollection();
        $this->environments = new ArrayCollection();
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
}
