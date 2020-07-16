<?php

namespace App\Entity;

use App\Repository\RecordsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecordsRepository::class)
 */
class Record
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
    private $cloudFlareId;

    /**
     * @ORM\ManyToOne(targetEntity=Domain::class, inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private $domain;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCloudFlareId(): ?string
    {
        return $this->cloudFlareId;
    }

    public function setCloudFlareId(string $cloudFlareId): self
    {
        $this->cloudFlareId = $cloudFlareId;

        return $this;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }
}
