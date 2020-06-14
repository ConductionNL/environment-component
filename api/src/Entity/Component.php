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
use Doctrine\Common\Collections\Criteria;
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
 *              "path"="/components/{id}/install",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="install",
 *                  "description"="Installs this component to a cluster"
 *              }
 *     },
 *     "helm_update"={
 *              "path"="/components/{id}/update",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="update",
 *                  "description"="Updates this component to a cluster"
 *              }
 *     },
 *     "get_change_logs"={
 *              "path"="/components/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *     "get_audit_trail"={
 *              "path"="/components/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          }
 * 		},
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ComponentRepository")
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
 */
class Component
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
     * @example environment component
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
     * @var string The unique commonground short code for this component
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
    private $code;

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
    private $githubRepository;

    /**
     * @var string A personal access token for github that can be used to trigger actions on this component
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $githubToken;

    /**
     * @var string the Helm Repository that contains this component
     *
     * @example https://github.com/ConductionNL/environment-component/api/helm
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private $helmRepository;

    /**
     * @var bool Is the component a core component
     *
     * @Groups({"read","write"})
     * @Gedmo\Versioned
     * @ORM\Column(type="boolean")
     */
    private $core = false;

    /**
     * @Groups({"write"})
     * @MaxDepth(1)
     * @ORM\OneToMany(targetEntity="App\Entity\Installation", mappedBy="component")
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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
            $installation->setComponent($this);
        }

        return $this;
    }

    public function removeInstallation(Installation $installation): self
    {
        if ($this->installations->contains($installation)) {
            $this->installations->removeElement($installation);
            // set the owning side to null (unless already changed)
            if ($installation->getComponent() === $this) {
                $installation->setComponent(null);
            }
        }

        return $this;
    }

    public function getGithubRepository(): ?string
    {
        return $this->githubRepository;
    }

    public function setGithubRepository(string $githubRepository): self
    {
        $this->githubRepository = $githubRepository;

        return $this;
    }

    public function getGithubToken(): ?string
    {
        return $this->githubToken;
    }

    public function setGithubToken(?string $githubToken): self
    {
        $this->githubToken = $githubToken;

        return $this;
    }

    public function getHelmRepository(): ?string
    {
        return $this->helmRepository;
    }

    public function setHelmRepository(string $helmRepository): self
    {
        $this->helmRepository = $helmRepository;

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

    public function getCore(): ?bool
    {
        return $this->core;
    }

    public function setCore(bool $core): self
    {
        $this->core = $core;

        return $this;
    }

    public function hasInstallationInEnvironment(Environment $environment): bool
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('environment', $environment));

        return count($this->getInstallations()->matching($criteria)) > 0;
    }
}
