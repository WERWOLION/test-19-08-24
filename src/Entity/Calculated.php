<?php

namespace App\Entity;

use App\Repository\CalculatedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CalculatedRepository::class)
 */
class Calculated
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"api_chat"})
     */
    private $id;

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
     * @ORM\ManyToOne(targetEntity=Offer::class, inversedBy="calculateds")
     * @Groups({"api_chat"})
     */
    private $offer;

    /**
     * @ORM\Column(type="float")
     */
    private $fullsumm = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $monthcount = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $procent = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $mounthpay = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDifferentiated = false;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"api_chat"})
     */
    private $status = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixID;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $truefullsumm;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $truemonthcount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $trueprocent;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $truemounthpay;

    /**
     * @ORM\ManyToOne(targetEntity=Bank::class, inversedBy="calculateds")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Groups({"api_chat"})
     */
    private $bank;

    public $bankEntity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstpay;

    /**
     * @ORM\OneToMany(targetEntity=Attachment::class, mappedBy="calculated", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $objectDocs;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bittrixPersonFolderId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bittrixObjectFolderId;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $truefirstpay;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $signData;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPayDone = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $newEventType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $newEventMessage;

    /**
     * @ORM\OneToOne(targetEntity=ChatRoom::class, mappedBy="calculated", cascade={"persist", "remove"})
     * @Groups({"api_chat"})
     */
    private $chatRoom;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $other = [];

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $motherCapSize;


    public function __toString()
    {
        $bankname = "Заявка без банка";
        if($this->bank){
            $bankname = $this->bank->getTitle();
        }
        if(isset($this->other['bankTitle'])){
            $bankname = $this->other['bankTitle'];
        }
        return '#' . $this->id . ': ' . $bankname;
    }

    public function __construct()
    {
        $this->objectDocs = new ArrayCollection();
    }

    static public function getSearchFields()
    {
        return [
            'buyer__lastname',
            'buyer__firstname',
            'buyer__middlename',
            'id',
            'offer__id',
            'fullsumm',
            'truefullsumm',
        ];
    }

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function getFullsumm(): ?float
    {
        return $this->fullsumm;
    }

    public function setFullsumm(float $fullsumm): self
    {
        $this->fullsumm = $fullsumm;

        return $this;
    }

    public function getMonthcount(): ?int
    {
        return $this->monthcount;
    }

    public function setMonthcount(int $monthcount): self
    {
        $this->monthcount = $monthcount;

        return $this;
    }

    public function getProcent(): ?float
    {
        return $this->procent;
    }

    public function setProcent(float $procent): self
    {
        $this->procent = $procent;

        return $this;
    }

    public function getMounthpay(): ?float
    {
        return $this->mounthpay;
    }

    public function setMounthpay(float $mounthpay): self
    {
        $this->mounthpay = $mounthpay;

        return $this;
    }

    public function getIsDifferentiated(): ?bool
    {
        return $this->isDifferentiated;
    }

    public function setIsDifferentiated(bool $isDifferentiated): self
    {
        $this->isDifferentiated = $isDifferentiated;

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

    public function getStatusColor(): ?string
    {
        if(in_array($this->status, [0, 10])) {
            return 'info';
        }
        if(in_array($this->status, [0, 10, 20, 30, 50])) {
            return 'primary';
        }
        if(in_array($this->status, [60, 70, 80, 100, 110, 115, 120])) {
            return 'info';
        }
        if(in_array($this->status, [40, 90])) {
            return 'warning';
        }
        if(in_array($this->status, [130])) {
            return 'success';
        }
        if(in_array($this->status, [-10, -20, -30])) {
            return 'danger';
        }
        return 'secondary';
    }


    public function getBitrixID(): ?string
    {
        return $this->bitrixID;
    }

    public function setBitrixID(?string $bitrixID): self
    {
        $this->bitrixID = $bitrixID;

        return $this;
    }

    public function getTruefullsumm(): ?float
    {
        return $this->truefullsumm;
    }

    public function setTruefullsumm(?float $truefullsumm): self
    {
        $this->truefullsumm = $truefullsumm;

        return $this;
    }

    public function getTruemonthcount(): ?int
    {
        return $this->truemonthcount;
    }

    public function setTruemonthcount(?int $truemonthcount): self
    {
        $this->truemonthcount = $truemonthcount;

        return $this;
    }

    public function getTrueprocent(): ?float
    {
        return $this->trueprocent;
    }

    public function setTrueprocent(?float $trueprocent): self
    {
        $this->trueprocent = $trueprocent;

        return $this;
    }

    public function getTruemounthpay(): ?float
    {
        return $this->truemounthpay;
    }

    public function setTruemounthpay(?float $truemounthpay): self
    {
        $this->truemounthpay = $truemounthpay;

        return $this;
    }

    public function getBank(): ?Bank
    {
        return $this->bank;
    }

    public function setBank(?Bank $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    public function getFirstpay(): ?int
    {
        return $this->firstpay;
    }

    public function setFirstpay(?int $firstpay): self
    {
        $this->firstpay = $firstpay;

        return $this;
    }

    /**
     * @return Collection|Attachment[]
     */
    public function getObjectDocs(): Collection
    {
        return $this->objectDocs;
    }

    public function addObjectDoc(Attachment $objectDoc): self
    {
        if (!$this->objectDocs->contains($objectDoc)) {
            $this->objectDocs[] = $objectDoc;
            $objectDoc->setCalculated($this);
        }

        return $this;
    }

    public function removeObjectDoc(Attachment $objectDoc): self
    {
        if ($this->objectDocs->removeElement($objectDoc)) {
            // set the owning side to null (unless already changed)
            if ($objectDoc->getCalculated() === $this) {
                $objectDoc->setCalculated(null);
            }
        }

        return $this;
    }

    public function getBittrixPersonFolderId(): ?int
    {
        return $this->bittrixPersonFolderId;
    }

    public function setBittrixPersonFolderId(?int $bittrixPersonFolderId): self
    {
        $this->bittrixPersonFolderId = $bittrixPersonFolderId;

        return $this;
    }

    public function getBittrixObjectFolderId(): ?int
    {
        return $this->bittrixObjectFolderId;
    }

    public function setBittrixObjectFolderId(?int $bittrixObjectFolderId): self
    {
        $this->bittrixObjectFolderId = $bittrixObjectFolderId;

        return $this;
    }

    public function getTruefirstpay(): ?float
    {
        return $this->truefirstpay;
    }

    public function setTruefirstpay(?float $truefirstpay): self
    {
        $this->truefirstpay = $truefirstpay;

        return $this;
    }

    public function getSignData(): ?string
    {
        return $this->signData;
    }

    public function setSignData(?string $signData): self
    {
        $this->signData = $signData;

        return $this;
    }

    public function getIsPayDone(): ?bool
    {
        return $this->isPayDone;
    }

    public function setIsPayDone(bool $isPayDone): self
    {
        $this->isPayDone = $isPayDone;

        return $this;
    }

    public function getNewEventType(): ?int
    {
        return $this->newEventType;
    }

    public function setNewEventType(?int $newEventType): self
    {
        $this->newEventType = $newEventType;

        return $this;
    }

    public function getNewEventMessage(): ?string
    {
        return $this->newEventMessage;
    }

    public function setNewEventMessage(?string $newEventMessage): self
    {
        $this->newEventMessage = $newEventMessage;

        return $this;
    }

    public function getChatRoom(): ?ChatRoom
    {
        return $this->chatRoom;
    }

    public function setChatRoom(?ChatRoom $chatRoom): self
    {
        // unset the owning side of the relation if necessary
        if ($chatRoom === null && $this->chatRoom !== null) {
            $this->chatRoom->setCalculated(null);
        }

        // set the owning side of the relation if necessary
        if ($chatRoom !== null && $chatRoom->getCalculated() !== $this) {
            $chatRoom->setCalculated($this);
        }

        $this->chatRoom = $chatRoom;

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

    public function getMotherCapSize(): ?float
    {
        return $this->motherCapSize;
    }

    public function setMotherCapSize(?float $motherCapSize): self
    {
        $this->motherCapSize = $motherCapSize;

        return $this;
    }

    public function isIsDifferentiated(): ?bool
    {
        return $this->isDifferentiated;
    }

    public function isIsPayDone(): ?bool
    {
        return $this->isPayDone;
    }

}
