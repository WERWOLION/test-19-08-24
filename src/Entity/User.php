<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Serializable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="Пользователь с таким Email уже зарегистрирован")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    public $passChanger;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isEmailConfirm;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emailCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $passCode;

    /**
     * @ORM\OneToMany(targetEntity=Log::class, mappedBy="user", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $logs;

    /**
     * @ORM\OneToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $avatar;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $middlename;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\OneToOne(targetEntity=Partner::class, inversedBy="user", cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $partner;

    /**
     * @ORM\OneToOne(targetEntity=Wallet::class, inversedBy="userAccount", cascade={"persist", "remove"})
     */
    private $wallet;

    /**
     * @ORM\ManyToOne(targetEntity=Town::class)
     */
    private $town;

    /**
     * @ORM\OneToMany(targetEntity=Offer::class, mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $offers;

    public $calculatedSynt = [];

    public function getMaxCaclStatus()
    {
        if (!count($this->calculatedSynt)) return 'Нет';
        if (isset(array_flip(Offer::OFFER_STATUS)[$this->calculatedSynt[0]->getStatus()])) {
            return array_flip(Offer::OFFER_STATUS)[$this->calculatedSynt[0]->getStatus()];
        }
        return 'Ошибка';
    }

    /**
     * @ORM\Column(type="boolean")
     */
    private $isBanned = false;

    /**
     * @ORM\OneToMany(targetEntity=Attachment::class, mappedBy="user")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    private $attachments;

    /**
     * @ORM\OneToMany(targetEntity=Buyer::class, mappedBy="creator", cascade={"remove"}, orphanRemoval=true)
     */
    private $buyers;

    /**
     * @ORM\OneToMany(targetEntity=Savedcontact::class, mappedBy="creator", cascade={"remove"}, orphanRemoval=true)
     */
    private $savedContacts;

    /**
     * @ORM\OneToMany(targetEntity=ChatRoom::class, mappedBy="user", cascade={"remove"}, orphanRemoval=true)
     */
    private $chatRooms;

    /**
     * @ORM\OneToMany(targetEntity=ChatMessage::class, mappedBy="user", cascade={"remove"}, orphanRemoval=true)
     */
    private $chatMessages;

    /**
     * @ORM\ManyToMany(targetEntity=ChatRoom::class, mappedBy="viewedByUsers")
     */
    private $viewedChatRooms;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixManagerID;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="myAdminedUsers")
     */
    private $myManager;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="myManager")
     */
    private $myAdminedUsers;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->buyers = new ArrayCollection();
        $this->savedContacts = new ArrayCollection();
        $this->chatRooms = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
        $this->viewedChatRooms = new ArrayCollection();
        $this->myAdminedUsers = new ArrayCollection();
    }


    public function getFio()
    {
        return implode(" ", [
            $this->lastname,
            $this->firstname,
            $this->middlename,
        ]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function __toString()
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIsEmailConfirm(): ?bool
    {
        return $this->isEmailConfirm;
    }

    public function setIsEmailConfirm(bool $isEmailConfirm): self
    {
        $this->isEmailConfirm = $isEmailConfirm;

        return $this;
    }

    public function getEmailCode(): ?string
    {
        return $this->emailCode;
    }

    public function setEmailCode(?string $emailCode): self
    {
        $this->emailCode = $emailCode;

        return $this;
    }

    public function getPassCode(): ?string
    {
        return $this->passCode;
    }

    public function setPassCode(?string $passCode): self
    {
        $this->passCode = $passCode;

        return $this;
    }

    /**
     * @return Collection|Log[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setUser($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getUser() === $this) {
                $log->setUser(null);
            }
        }

        return $this;
    }

    public function getAvatar(): ?attachment
    {
        return $this->avatar;
    }

    public function setAvatar(?attachment $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getMiddlename(): ?string
    {
        return $this->middlename;
    }

    public function setMiddlename(?string $middlename): self
    {
        $this->middlename = $middlename;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): self
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    /**
     * @return Collection|Offer[]
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers[] = $offer;
            $offer->setUser($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getUser() === $this) {
                $offer->setUser(null);
            }
        }

        return $this;
    }

    public function getIsBanned(): ?bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;

        return $this;
    }

    /**
     * @return Collection|Attachment[]
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments[] = $attachment;
            $attachment->setUser($this);
        }

        return $this;
    }

    public function removeAttachment(Attachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getUser() === $this) {
                $attachment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Buyer[]
     */
    public function getBuyers(): Collection
    {
        return $this->buyers;
    }

    public function addBuyer(Buyer $buyer): self
    {
        if (!$this->buyers->contains($buyer)) {
            $this->buyers[] = $buyer;
            $buyer->setCreator($this);
        }

        return $this;
    }

    public function removeBuyer(Buyer $buyer): self
    {
        if ($this->buyers->removeElement($buyer)) {
            // set the owning side to null (unless already changed)
            if ($buyer->getCreator() === $this) {
                $buyer->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Savedcontact[]
     */
    public function getSavedContacts(): Collection
    {
        return $this->savedContacts;
    }

    public function addSavedContact(Savedcontact $savedContact): self
    {
        if (!$this->savedContacts->contains($savedContact)) {
            $this->savedContacts[] = $savedContact;
            $savedContact->setCreator($this);
        }

        return $this;
    }

    public function removeSavedContact(Savedcontact $savedContact): self
    {
        if ($this->savedContacts->removeElement($savedContact)) {
            // set the owning side to null (unless already changed)
            if ($savedContact->getCreator() === $this) {
                $savedContact->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ChatRoom[]
     */
    public function getChatRooms(): Collection
    {
        return $this->chatRooms;
    }

    public function addChatRoom(ChatRoom $chatRoom): self
    {
        if (!$this->chatRooms->contains($chatRoom)) {
            $this->chatRooms[] = $chatRoom;
            $chatRoom->setUser($this);
        }

        return $this;
    }

    public function removeChatRoom(ChatRoom $chatRoom): self
    {
        if ($this->chatRooms->removeElement($chatRoom)) {
            // set the owning side to null (unless already changed)
            if ($chatRoom->getUser() === $this) {
                $chatRoom->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ChatMessage[]
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(ChatMessage $chatMessage): self
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages[] = $chatMessage;
            $chatMessage->setUser($this);
        }

        return $this;
    }

    public function removeChatMessage(ChatMessage $chatMessage): self
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            // set the owning side to null (unless already changed)
            if ($chatMessage->getUser() === $this) {
                $chatMessage->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ChatRoom[]
     */
    public function getViewedChatRooms(): Collection
    {
        return $this->viewedChatRooms;
    }

    public function addViewedChatRoom(ChatRoom $viewedChatRoom): self
    {
        if (!$this->viewedChatRooms->contains($viewedChatRoom)) {
            $this->viewedChatRooms[] = $viewedChatRoom;
            $viewedChatRoom->addViewedByUser($this);
        }

        return $this;
    }

    public function removeViewedChatRoom(ChatRoom $viewedChatRoom): self
    {
        if ($this->viewedChatRooms->removeElement($viewedChatRoom)) {
            $viewedChatRoom->removeViewedByUser($this);
        }

        return $this;
    }

    public function getBitrixManagerID(): ?string
    {
        return $this->bitrixManagerID;
    }

    public function setBitrixManagerID(?string $bitrixManagerID): self
    {
        $this->bitrixManagerID = $bitrixManagerID;

        return $this;
    }

    public function getMyManager(): ?self
    {
        return $this->myManager;
    }

    public function setMyManager(?self $myManager): self
    {
        $this->myManager = $myManager;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getMyAdminedUsers(): Collection
    {
        return $this->myAdminedUsers;
    }

    public function addMyAdminedUser(self $myAdminedUser): self
    {
        if (!$this->myAdminedUsers->contains($myAdminedUser)) {
            $this->myAdminedUsers[] = $myAdminedUser;
            $myAdminedUser->setMyManager($this);
        }

        return $this;
    }

    public function removeMyAdminedUser(self $myAdminedUser): self
    {
        if ($this->myAdminedUsers->removeElement($myAdminedUser)) {
            // set the owning side to null (unless already changed)
            if ($myAdminedUser->getMyManager() === $this) {
                $myAdminedUser->setMyManager(null);
            }
        }

        return $this;
    }

    public function isIsEmailConfirm(): ?bool
    {
        return $this->isEmailConfirm;
    }

    public function isIsBanned(): ?bool
    {
        return $this->isBanned;
    }
}
