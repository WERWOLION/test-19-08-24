<?php

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=ChatMessageRepository::class)
 */
class ChatMessage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="chatMessages")
     * @Ignore()
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isNew = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isAdminMessage = false;

    /**
     * @ORM\ManyToOne(targetEntity=ChatRoom::class, inversedBy="messages")
     * @ORM\JoinColumn(nullable=false)
     * @Ignore()
     */
    private $chatRoom;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getIsNew(): ?bool
    {
        return $this->isNew;
    }

    public function setIsNew(bool $isNew): self
    {
        $this->isNew = $isNew;

        return $this;
    }

    public function getIsAdminMessage(): ?bool
    {
        return $this->isAdminMessage;
    }

    public function setIsAdminMessage(bool $isAdminMessage): self
    {
        $this->isAdminMessage = $isAdminMessage;

        return $this;
    }

    public function getChatRoom(): ?ChatRoom
    {
        return $this->chatRoom;
    }

    public function setChatRoom(?ChatRoom $chatRoom): self
    {
        $this->chatRoom = $chatRoom;

        return $this;
    }

    public function isIsNew(): ?bool
    {
        return $this->isNew;
    }

    public function isIsAdminMessage(): ?bool
    {
        return $this->isAdminMessage;
    }
}
