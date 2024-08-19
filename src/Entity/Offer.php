<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=OfferRepository::class)
 */
class Offer
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $creditTarget;

    const CREDIT_TARGET = [
        "Ипотека" => "ипотека",
        "Рефинансирование" => "рефинансирование",
        // "Займ Материнский капитал" => "материнский",
        "Деньги под залог" => "залог"
    ];
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $salerType;

    const SALER_TYPE = [
//        "Физ. лицо (готовое жилье)" => "физлицо",
//        "Застройщик (строящееся жилье)" => "застройщик",
//        "Застройщик/Юр. лицо (готовое жилье)" => "юрлицо",
//        "Физ. лицо (строящееся жилье)" => "физлицо_ст",
        "Физлицо по ДКП" => "физ_по_дкп",
        "Юрлицо/ИП по ДКП" => "юр_по_дкп",
        "ИЖС подряд/собственными силами" => "ижс",
        "Застройщик по ДДУ" => "застр_по_дду",
        "Юрлицо/ИП по ДУПТ" => "юр_по_дупт",
        "Физлицо по ДУПТ" => "физ_по_дупт"
    ];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $calcPriceType = 10;

    const CALC_PRICE_TYPE = [
        "Стоимости жилья" => 10,
        "Ежемесячному платежу" => 20,
        "Доходу" => 30,
    ];

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $objectType;

    const OBJECT_TYPE = [
        "Квартира" => "квартира",
        "Последняя доля или комната" => "комната",
        "Дом с землей" => "дом",
        "Таунхаус" => "таунхаус",
        "Апартаменты" => "апартаменты",
        "Коммерческая недвижимость" => "кн",
        "ИЖС (строительство дома)" => "ижс",
        "Отдельная комната/доля" => "отд_доля",
        "Земельный участок" => "участок"
    ];

    /**
     * @ORM\Column(type="boolean")
     */
    private $isMotherCap = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stateSupport = 15662;

    const STATESUPPORT_TYPE = [
        "Без господдержки" => 15662,
        "Арктическая/Дальневосточная ипотека" => 15664,
        "Семейная ипотека" => 15666,
//        "Военная ипотека" => 15668,
        "IT-ипотека" => 15956,
    ];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $age;

    /**
     * @ORM\Column(type="integer")
     */
    private $nationality = 10;

    const NATIONALITY_TYPE = [
//        "РФ" => 10,
//        "Вид на жительство" => 20,
//        "Миграционная карта" => 30,
        "РФ" => 10,
        "Иностранное" => 20,
    ];

    /**
     * @ORM\Column(type="integer")
     */
    private $hiringType = 10;

    const HIRING_TYPE = [
        "Найм" => 10,
        "ИП" => 20,
        "Бизнес" => 30,
        "Самозанятый" => 40,
    ];

    /**
     * @ORM\Column(type="integer")
     */
    private $proofMoney = 0;

    const PROOF_MONEY_TYPE = [
        "Справка о доходах (2НДФЛ)" => 10,
        "Справка по форме банка" => 20,
        "Без подтверждения (по 2 документам)" => 30,
        "Выписка из СФР" => 40,
        "Декларация/Выписка по р/с" => 50,
        "2НДФЛ (рассмотрение как наёмного сотрудника)" => 60,
        "Выписка из СФР (рассмотрение как наёмного сотрудника)" => 70,
        "Справка о доходах (из “Мой налог”)" => 80,
    ];

    /**
     * @ORM\Column(type="integer")
     */
    private $status = 0;

    const OFFER_STATUS = [
        "Новая (не сохранено)" => 0,
        "Сохранена" => 10,
        "Отправлена" => 20,
        "Первичная проверка" => 30,
        "Доработка заявки" => 40,
        "Отправлена в банк" => 50,
        "Одобрена" => 60,
        "Закончилось одобрение" => 70,
        "Объект подобран" => 80,
        "Доработка объекта" => 90,
        "Согласование объекта" => 100,
        "Согласование сделки" => 110,
        "Сделка согласована" => 115,
        "Сделка" => 120,
        "Кредит выдан" => 130,
        "Отказ банка" => -10,
        "Отказ клиента" => -20,
        "Отменена" => -30,
    ];

    const BITRIX24_STATUS = [
        "NEW" => 20,
        37 => 30,
        33 => 40,
        21 => 50,
        23 => 60,
        42 => 70,
        22 => 80,
        40 => 90,
        38 => 100,
        41 => 110,
        43 => 115,
        26 => 120,
        "WON" => 130,
        36 => -10,
        28 => -10,
        "LOSE" => -10,
        "APOLOGY" => -20,
        30 => -20,
        34 => -20,
        27 => -10,
        29 => -10,
        39 => -30,
    ];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixFolderID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitrixDocsID;

    /**
     * @ORM\OneToMany(targetEntity=Buyer::class, mappedBy="offercobuyer", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $cobuyers;

    /**
     * @ORM\OneToOne(targetEntity=Buyer::class, cascade={"persist", "remove"}, fetch="EAGER")
     */
    private $buyer;

    /**
     * @ORM\OneToMany(targetEntity=Calculated::class, mappedBy="offer", fetch="EAGER", cascade={"persist", "remove"})
     */
    private $calculateds;

    /**
     * @ORM\OneToMany(targetEntity=Attachment::class, mappedBy="offer", cascade={"persist", "remove"})
     */
    private $documents;

    /**
     * @ORM\ManyToOne(targetEntity=Town::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="offers")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $locality;

    /**
     * @ORM\Column(type="array")
     */
    private $other = [];


    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isTarget = false;

    const PLEDGE_TARGETS = [
        "Не целевой" => 0,
        "Целевой" => 1
    ];

    public $time;
    public $cost;
    public $firstpay;
    public $motherCapSize;

    public $withAddAmount;
    public $addAmount;
    public $withConsolidation;
    public $creditsCount;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isMilitaryMortgage = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ijs;

    const IJS = [
        'В собственности' => 16084,
        'В ипотеку' => 16086,
    ];

    public function __construct()
    {
        $this->cobuyers = new ArrayCollection();
        $this->calculateds = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->id . " (" . $this->user . ")";
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

    public function getCreditTarget(): ?string
    {
        return $this->creditTarget;
    }

    public function setCreditTarget(?string $creditTarget): self
    {
        $this->creditTarget = $creditTarget;

        return $this;
    }

    public function getSalerType(): ?string
    {
        return $this->salerType;
    }

    public function setSalerType(?string $salerType): self
    {
        $this->salerType = $salerType;

        return $this;
    }

    public function getCalcPriceType(): ?int
    {
        return $this->calcPriceType;
    }

    public function setCalcPriceType(?int $calcPriceType): self
    {
        $this->calcPriceType = $calcPriceType;

        return $this;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function setObjectType(?string $objectType): self
    {
        $this->objectType = $objectType;

        return $this;
    }

    public function getIsMotherCap(): ?bool
    {
        return $this->isMotherCap;
    }

    public function setIsMotherCap(bool $isMotherCap): self
    {
        $this->isMotherCap = $isMotherCap;

        return $this;
    }

    public function getStateSupport(): ?int
    {
        return $this->stateSupport;
    }

    public function setStateSupport(int $stateSupport): self
    {
        $this->stateSupport = $stateSupport;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getNationality(): ?int
    {
        return $this->nationality;
    }

    public function setNationality(int $nationality): self
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getHiringType(): ?int
    {
        return $this->hiringType;
    }

    public function setHiringType(int $hiringType): self
    {
        $this->hiringType = $hiringType;

        return $this;
    }

    public function getProofMoney(): ?int
    {
        return $this->proofMoney;
    }

    public function setProofMoney(int $proofMoney): self
    {
        $this->proofMoney = $proofMoney;

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

    public function getBitrixFolderID(): ?string
    {
        return $this->bitrixFolderID;
    }

    public function setBitrixFolderID(?string $bitrixFolderID): self
    {
        $this->bitrixFolderID = $bitrixFolderID;

        return $this;
    }

    public function getBitrixDocsID(): ?string
    {
        return $this->bitrixDocsID;
    }

    public function setBitrixDocsID(?string $bitrixDocsID): self
    {
        $this->bitrixDocsID = $bitrixDocsID;

        return $this;
    }

    /**
     * @return Collection|Buyer[]
     */
    public function getCobuyers(): Collection
    {
        return $this->cobuyers;
    }

    public function addCobuyer(Buyer $cobuyer): self
    {
        if (!$this->cobuyers->contains($cobuyer)) {
            $this->cobuyers[] = $cobuyer;
            $cobuyer->setOffercobuyer($this);
        }

        return $this;
    }

    public function removeCobuyer(Buyer $cobuyer): self
    {
        if ($this->cobuyers->removeElement($cobuyer)) {
            // set the owning side to null (unless already changed)
            if ($cobuyer->getOffercobuyer() === $this) {
                $cobuyer->setOffercobuyer(null);
            }
        }

        return $this;
    }

    public function getBuyer(): ?Buyer
    {
        return $this->buyer;
    }

    public function setBuyer(?Buyer $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    /**
     * @return Collection|Calculated[]
     */
    public function getCalculateds(): Collection
    {
        return $this->calculateds;
    }

    public function addCalculated(Calculated $calculated): self
    {
        if (!$this->calculateds->contains($calculated)) {
            $this->calculateds[] = $calculated;
            $calculated->setOffer($this);
        }

        return $this;
    }

    public function removeCalculated(Calculated $calculated): self
    {
        if ($this->calculateds->removeElement($calculated)) {
            // set the owning side to null (unless already changed)
            if ($calculated->getOffer() === $this) {
                $calculated->setOffer(null);
            }
        }

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
            $document->setOffer($this);
        }

        return $this;
    }

    public function removeDocument(Attachment $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getOffer() === $this) {
                $document->setOffer(null);
            }
        }

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $User): self
    {
        $this->user = $User;

        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(string $locality): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function getOther(): ?array
    {
        return $this->other;
    }

    public function setOther(array $other): self
    {
        $this->other = $other;

        return $this;
    }

    public function isIsMotherCap(): ?bool
    {
        return $this->isMotherCap;
    }

    public function getIsTarget(): bool
    {
        return $this->isTarget;
    }

    public function setIsTarget(bool $isTarget): self
    {
        $this->isTarget = $isTarget;

        return $this;
    }

    public function getIsMilitaryMortgage(): ?bool
    {
        return $this->isMilitaryMortgage;
    }

    public function setIsMilitaryMortgage(bool $isMilitaryMortgage): self
    {
        $this->isMilitaryMortgage = $isMilitaryMortgage;

        return $this;
    }

    public function getIjs(): ?string
    {
        return $this->ijs;
    }

    public function setIjs(?string $ijs): self
    {
        $this->ijs = $ijs;

        return $this;
    }
}
