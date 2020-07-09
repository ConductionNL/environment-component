<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\OpenStackTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=OpenStackTemplateRepository::class)
 */
class OpenStackTemplate
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
     * @ORM\Column(type="string", length=255)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $masterFlavour;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nodeFlavour;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $volumeSize;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $versionTag;

    /**
     * @ORM\OneToMany(targetEntity=Cluster::class, mappedBy="template")
     */
    private $clusters;

    /**
     * @ORM\Column(type="integer")
     */
    private $nodeCount;

    public function __construct()
    {
        $this->clusters = new ArrayCollection();
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

    public function getVolumeSize(): ?string
    {
        return $this->volumeSize;
    }

    public function setVolumeSize(string $volumeSize): self
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
