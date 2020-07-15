<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\OpenStackTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An entity describing OpenStack templates
 *
 * This entity contains the fields that are needed to create openstack templates, which in tern are needed to create openstack clusters
 *
 * @ApiResource(
 *     	normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     	denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 * )
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 * @ORM\Entity(repositoryClass=OpenStackTemplateRepository::class)
 *
 * @ApiFilter(OrderFilter::class, properties={"name","dateCreated","dateModified"})
 * @ApiFilter(DateFilter::class, properties={"dateCreated","dateModified" })
 */
class OpenStackTemplate
{
    /**
     * @var UuidInterface The Uuid identifier of this openstack template
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
     * @var string The name of this openstack template
     *
     * @example fuga small
     *
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string The OS image that should be used **WARNING: THIS IS PROVIDER DEPENDENT**
     *
     * @example Ubuntu 20.04LTS
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $image;

    /**
     * @var string The type of node that should be used as master **WARNING: THIS IS PROVIDER DEPENDENT**
     *
     * @example c3.medium
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $masterFlavour;

    /**
     * @var string The type of node that should be used as nodes **WARNING: THIS IS PROVIDER DEPENDENT** Assumes masterFlavour when not set
     *
     * @example c3.small
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nodeFlavour;

    /**
     * @var int Size of the volumes attached to the nodes in GB
     *
     * @example 10
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $volumeSize;

    /**
     * @var string The version tag of the Kubernetes version
     *
     * @example v1.16.8
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $versionTag;

    /**
     * @var ArrayCollection The clusters that use this template
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity=Cluster::class, mappedBy="template")
     */
    private $clusters;

    /**
     * @var int The number of nodes the cluster needs
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer")
     */
    private $nodeCount;

    public function __construct()
    {
        $this->clusters = new ArrayCollection();
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getMasterFlavour(): ?string
    {
        return $this->masterFlavour;
    }

    public function setMasterFlavour(string $masterFlavour): self
    {
        $this->masterFlavour = $masterFlavour;

        return $this;
    }

    public function getNodeFlavour(): ?string
    {
        return $this->nodeFlavour;
    }

    public function setNodeFlavour(?string $nodeFlavour): self
    {
        $this->nodeFlavour = $nodeFlavour;

        return $this;
    }

    public function getVolumeSize(): ?int
    {
        return $this->volumeSize;
    }

    public function setVolumeSize(int $volumeSize): self
    {
        $this->volumeSize = $volumeSize;

        return $this;
    }

    public function getVersionTag(): ?string
    {
        return $this->versionTag;
    }

    public function setVersionTag(string $versionTag): self
    {
        $this->versionTag = $versionTag;

        return $this;
    }

    /**
     * @return Collection|Cluster[]
     */
    public function getClusters(): Collection
    {
        return $this->clusters;
    }

    public function addCluster(Cluster $cluster): self
    {
        if (!$this->clusters->contains($cluster)) {
            $this->clusters[] = $cluster;
            $cluster->setTemplate($this);
        }

        return $this;
    }

    public function removeCluster(Cluster $cluster): self
    {
        if ($this->clusters->contains($cluster)) {
            $this->clusters->removeElement($cluster);
            // set the owning side to null (unless already changed)
            if ($cluster->getTemplate() === $this) {
                $cluster->setTemplate(null);
            }
        }

        return $this;
    }

    public function getNodeCount(): ?int
    {
        return $this->nodeCount;
    }

    public function setNodeCount(int $nodeCount): self
    {
        $this->nodeCount = $nodeCount;

        return $this;
    }
}
