<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PartnerRepository::class)
 */
class Partner
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
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Choice({0,1,2,3,4})
     */
    private $type = 0;

    const PARTNER_TYPE = [
        0 => "Не задано",
        1 => "Физическое лицо",
        2 => "Самозанятый",
        3 => "ИП",
        4 => "Юр. лицо"
    ];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shortname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fullname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Type("numeric", message="Значение должно быть числом")
     */
    private $inn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ogrn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $legaladress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $postadress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Choice({"osno", "usn"})
     */
    private $nalogtype;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactface;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixContactID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixRegDealID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixReferalDealID;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="partner", cascade={"persist", "remove"})
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bankname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Type("numeric", message="Значение должно быть числом")
     */
    private $bankbik;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Type("numeric", message="Значение должно быть числом")
     */
    private $bankaccount;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $other = [];

    /**
     * @ORM\OneToMany(targetEntity=Attachment::class, mappedBy="partnerlink", cascade={"persist", "remove"})
     */
    private $documents;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalsumm = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $monthpaynumber = 0;

    /**
     * @ORM\Column(type="array")
     */
    private $bonusHistory = [];

    /**
     * @ORM\ManyToOne(targetEntity=Partner::class, inversedBy="referals")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $myReferal;

    /**
     * @ORM\OneToMany(targetEntity=Partner::class, mappedBy="myReferal")
     */
    private $referals;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $referalStatus;

    const REFERAL_STATUS = [
        0 => "Не задано",
        1 => "Новая заявка",
        2 => "Подана заявка",
        3 => "Кредит выдан",
        4 => "Бонусы начислены",
        5 => "Запрос на выплату",
        6 => "Заявка завершена",
    ];


    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->referals = new ArrayCollection();
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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getReferalStatus(): ?int
    {
        return $this->referalStatus;
    }

    public function setReferalStatus(int $referalStatus): self
    {
        $this->referalStatus = $referalStatus;

        return $this;
    }

    public function getShortname(): ?string
    {
        return $this->shortname;
    }

    public function setShortname(?string $shortname): self
    {
        $this->shortname = $shortname;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getOgrn(): ?string
    {
        return $this->ogrn;
    }

    public function setOgrn(?string $ogrn): self
    {
        $this->ogrn = $ogrn;

        return $this;
    }

    public function getLegaladress(): ?string
    {
        return $this->legaladress;
    }

    public function setLegaladress(?string $legaladress): self
    {
        $this->legaladress = $legaladress;

        return $this;
    }

    public function getNalogtype(): ?string
    {
        return $this->nalogtype;
    }

    public function setNalogtype(?string $nalogtype): self
    {
        $this->nalogtype = $nalogtype;

        return $this;
    }

    public function getContactface(): ?string
    {
        return $this->contactface;
    }

    public function setContactface(?string $contactface): self
    {
        $this->contactface = $contactface;

        return $this;
    }

    public function getBitrixContactID(): ?string
    {
        return $this->bitrixContactID;
    }

    public function setBitrixContactID(?string $bitrixContactID): self
    {
        $this->bitrixContactID = $bitrixContactID;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setPartner(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getPartner() !== $this) {
            $user->setPartner($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getBankname(): ?string
    {
        return $this->bankname;
    }

    public function setBankname(string $bankname): self
    {
        $this->bankname = $bankname;

        return $this;
    }

    public function getBankbik(): ?string
    {
        return $this->bankbik;
    }

    public function setBankbik(?string $bankbik): self
    {
        $this->bankbik = $bankbik;

        return $this;
    }

    public function getBankaccount(): ?string
    {
        return $this->bankaccount;
    }

    public function setBankaccount(?string $bankaccount): self
    {
        $this->bankaccount = $bankaccount;

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

    /**
     * @return Collection|Attachment[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Attachment $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setPartnerlink($this);
        }

        return $this;
    }

    public function removeDocument(Attachment $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getPartnerlink() === $this) {
                $document->setPartnerlink(null);
            }
        }

        return $this;
    }

    public function getTotalsumm(): ?float
    {
        return $this->totalsumm;
    }

    public function setTotalsumm(?float $totalsumm): self
    {
        $this->totalsumm = $totalsumm;

        return $this;
    }

    public function getMonthpaynumber(): ?float
    {
        return $this->monthpaynumber;
    }

    public function setMonthpaynumber(?float $monthpaynumber): self
    {
        $this->monthpaynumber = $monthpaynumber;

        return $this;
    }


    public function __toString()
    {
        // to show the name of the Category in the select
        return $this->id . " " . $this->fullname;
        // to show the id of the Category in the select
        // return $this->id;
    }

    public function getPostadress(): ?string
    {
        return $this->postadress;
    }

    public function setPostadress(?string $postadress): self
    {
        $this->postadress = $postadress;

        return $this;
    }

    public function getBonusHistory(): ?array
    {
        return $this->bonusHistory;
    }

    public function setBonusHistory(array $bonusHistory): self
    {
        $this->bonusHistory = $bonusHistory;

        return $this;
    }

    public function getBitrixRegDealID(): ?string
    {
        return $this->bitrixRegDealID;
    }

    public function setBitrixRegDealID(?string $bitrixRegDealID): self
    {
        $this->bitrixRegDealID = $bitrixRegDealID;

        return $this;
    }

    public function getBitrixReferalDealID(): ?string
    {
        return $this->bitrixReferalDealID;
    }

    public function setBitrixReferalDealID(?string $bitrixReferalDealID): self
    {
        $this->bitrixReferalDealID = $bitrixReferalDealID;

        return $this;
    }

    public function getMyReferal(): ?self
    {
        return $this->myReferal;
    }

    public function setMyReferal(?self $myReferal): self
    {
        $this->myReferal = $myReferal;

        return $this;
    }

    /**
     * @return Collection<int, Partner>
     */
    public function getReferals(): Collection
    {
        return $this->referals;
    }

    public function addReferal(Partner $referal): self
    {
        if (!$this->referals->contains($referal)) {
            $this->referals->add($referal);
            $referal->setMyReferal($this);
        }

        return $this;
    }

    public function removeReferal(Partner $referal): self
    {
        if ($this->referals->removeElement($referal)) {
            // set the owning side to null (unless already changed)
            if ($referal->getMyReferal() === $this) {
                $referal->setMyReferal(null);
            }
        }

        return $this;
    }
}
