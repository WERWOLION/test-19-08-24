<?php

namespace App\Entity;

use App\Repository\ChatRoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass=ChatRoomRepository::class)
 */
class ChatRoom
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
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="chatRooms")
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity=Calculated::class, inversedBy="chatRoom")
     */
    private $calculated;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOpen = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isGenericDialog = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fio;

    /**
     * @ORM\OneToMany(targetEntity=ChatMessage::class, mappedBy="chatRoom", orphanRemoval=true)
     */
    private $messages;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="viewedChatRooms")
     */
    private $viewedByUsers;



    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->viewedByUsers = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->id . "/" . $this->title . "/" . $this->user;
    }

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

    public function getCalculated(): ?Calculated
    {
        return $this->calculated;
    }

    public function setCalculated(?Calculated $calculated): self
    {
        $this->calculated = $calculated;

        return $this;
    }

    public function getIsOpen(): ?bool
    {
        return $this->isOpen;
    }

    public function setIsOpen(bool $isOpen): self
    {
        $this->isOpen = $isOpen;

        return $this;
    }

    public function getIsGenericDialog(): ?bool
    {
        return $this->isGenericDialog;
    }

    public function setIsGenericDialog(bool $isGenericDialog): self
    {
        $this->isGenericDialog = $isGenericDialog;

        return $this;
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

    /**
     * @return Collection|ChatMessage[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setChatRoom($this);
        }

        return $this;
    }

    public function removeMessage(ChatMessage $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getChatRoom() === $this) {
                $message->setChatRoom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getViewedByUsers(): Collection
    {
        return $this->viewedByUsers;
    }

    public function addViewedByUser(User $viewedByUser): self
    {
        if (!$this->viewedByUsers->contains($viewedByUser)) {
            $this->viewedByUsers[] = $viewedByUser;
        }

        return $this;
    }

    public function removeViewedByUser(User $viewedByUser): self
    {
        $this->viewedByUsers->removeElement($viewedByUser);
        return $this;
    }

    public function clearViewedByUser(): self
    {
        $this->viewedByUsers->clear();
        return $this;
    }

    public function getFio(): ?string
    {
        return $this->fio;
    }

    public function setFio(?string $fio): self
    {
        $this->fio = $fio;

        return $this;
    }

    public function isIsOpen(): ?bool
    {
        return $this->isOpen;
    }

    public function isIsGenericDialog(): ?bool
    {
        return $this->isGenericDialog;
    }

}
