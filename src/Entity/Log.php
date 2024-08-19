<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=LogRepository::class)
 */
class Log
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $entityId = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $entity_type = "";

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $other = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $status = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isNotyf = false;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="logs")
     */
    private $user;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $viewedBy = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getIsNotyf(): ?bool
    {
        return $this->isNotyf;
    }

    public function setIsNotyf(bool $isNotyf): self
    {
        $this->isNotyf = $isNotyf;

        return $this;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entity_type;
    }

    public function setEntityType(?string $entity_type): self
    {
        $this->entity_type = $entity_type;

        return $this;
    }

    public function getOther(): ?array
    {
        return $this->other;
    }

    public function setOther(?array $other): self
    {
        $this->other = $other;

        return $this;
    }

    public function getViewedBy(): ?array
    {
        return $this->viewedBy;
    }

    public function setViewedBy(?array $viewedBy): self
    {
        $this->viewedBy = $viewedBy;

        return $this;
    }

    public function isIsNotyf(): ?bool
    {
        return $this->isNotyf;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
