<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PostRepository;

/**
 * @ORM\Entity(repositoryClass=PostRepository::class)
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublish = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isAnon = true;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $slug;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $other = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getOther(): array
    {
        return $this->other;
    }

    public function setOther(?array $other): self
    {
        $this->other = $other;

        return $this;
    }

    public function isIsPublish(): ?bool
    {
        return $this->isPublish;
    }

    public function setIsPublish(bool $isPublish): self
    {
        $this->isPublish = $isPublish;

        return $this;
    }

    public function isIsAnon(): ?bool
    {
        return $this->isAnon;
    }

    public function setIsAnon(bool $isAnon): self
    {
        $this->isAnon = $isAnon;

        return $this;
    }
}
