<?php

namespace App\Entity;

use App\Repository\BankMainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=BankMainRepository::class)
 */
class BankMain
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
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    //логотип банка. Нужен только для админки.
    public $logoId;

    public $referenceFile;

    /**
     * @ORM\Column(type="float")
     */
    private $bonusProcent = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $bonusPledge = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $bonusStateSupport = 0;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $salerTypes = [
        'isPhysical' => true,
        'isDeveloper' => true,
        'isBisnes' => true,
    ];

    /**
     * @ORM\Column(type="boolean")
     */
    private $isWarCap = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSocial = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isFamily = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isIT = false;

    /**
     * @ORM\OneToOne(targetEntity=BankOption::class, cascade={"all"})
     */
    private $ipotekaOptions;

    /**
     * @ORM\OneToOne(targetEntity=BankOption::class, cascade={"all"})
     */
    private $refinanceOptions;

    /**
     * @ORM\OneToOne(targetEntity=BankOption::class, cascade={"persist", "remove"})
     */
    private $pledgeOptions;

    /**
     * @ORM\Column(type="array")
     */
    private $other = [];

    /**
     * @ORM\Column(type="array")
     */
    private $minMax = [];


    /**
     * @ORM\Column(type="array")
     */
    private $towns = [];

    public $isAccept = true; //Нужно для фильтра. Показывает подходит ли банк для заявки
    public $errorMess = ""; //Если банк не подходит, то здесь сообщение
    public $calcData = [
        'rate' => 0, //процентная ставка
        'monthlyPayment' => 0,
        'creditPart' => 0,
        'bodySumm' => 0,
        'creditYear' => 0,
        'creditYearTxt' => "лет",
        'creditMonth' => 0,
    ];

    public $calcDataDinamic = [];

    /**
     * @ORM\Column(type="array")
     */
    private $bonusProcentDinamic = [];

    /**
     * @ORM\Column(type="array")
     */
    private $bonusPledgeDinamic = [];

    /**
     * @ORM\Column(type="array")
     */
    private $bonusStateSupportDinamic = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $stateIpotekaRefEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $marketIpotekaEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $marketIpotekaRefEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $stateIpotekaEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $market_ipoteka_pledge_enabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $state_ipoteka_pledge_enabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $proofMoney2docEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withAddAmountEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withConsolidationEnabled;

    /**
     * @ORM\OneToMany(targetEntity=BankBonusType::class, mappedBy="bank")
     */
    private $bankBonusTypes;

    /**
     * @ORM\Column(type="boolean")
     */
    private $proofMoney2docSelfEmployedEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $proofMoneySfrBusinessEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $proofMoney2ndflBusinessEnabled;

    public function __construct()
    {
        $this->ipotekaOptions = new BankOption();
        $this->refinanceOptions = new BankOption();
        $this->pledgeOptions = new BankOption();
        $this->bankBonusTypes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBonusProcent(): ?float
    {
        return $this->bonusProcent;
    }

    public function setBonusProcent(float $bonusProcent): self
    {
        $this->bonusProcent = $bonusProcent;

        return $this;
    }

    public function getSalerTypes(): ?array
    {
        return $this->salerTypes;
    }

    public function setSalerTypes(?array $salerTypes): self
    {
        $this->salerTypes = $salerTypes;

        return $this;
    }

    public function getIsWarCap(): ?bool
    {
        return $this->isWarCap;
    }

    public function setIsWarCap(bool $isWarCap): self
    {
        $this->isWarCap = $isWarCap;

        return $this;
    }

    public function getIsSocial(): ?bool
    {
        return $this->isSocial;
    }

    public function setIsSocial(bool $isSocial): self
    {
        $this->isSocial = $isSocial;

        return $this;
    }

    public function getIsFamily(): ?bool
    {
        return $this->isFamily;
    }

    public function setIsFamily(bool $isFamily): self
    {
        $this->isFamily = $isFamily;

        return $this;
    }

    public function getIpotekaOptions(): ?BankOption
    {
        return $this->ipotekaOptions;
    }

    public function setIpotekaOptions(BankOption $ipotekaOptions): self
    {
        $this->ipotekaOptions = $ipotekaOptions;

        return $this;
    }

    public function getRefinanceOptions(): ?BankOption
    {
        return $this->refinanceOptions;
    }

    public function setRefinanceOptions(BankOption $refinanceOptions): self
    {
        $this->refinanceOptions = $refinanceOptions;

        return $this;
    }

    public function getPledgeOptions(): ?BankOption
    {
        return $this->pledgeOptions;
    }

    public function setPledgeOptions(BankOption $pledgeOptions): self
    {
        $this->pledgeOptions = $pledgeOptions;

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

    public function getMinMax(): ?array
    {
        return $this->minMax;
    }

    public function setMinMax(array $minMax): self
    {
        $this->minMax = $minMax;

        return $this;
    }

    public function getTowns(): ?array
    {
        return $this->towns;
    }

    public function setTowns(array $towns): self
    {
        $this->towns = $towns;

        return $this;
    }

    public function isIsWarCap(): ?bool
    {
        return $this->isWarCap;
    }

    public function isIsSocial(): ?bool
    {
        return $this->isSocial;
    }

    public function isIsFamily(): ?bool
    {
        return $this->isFamily;
    }

    public function getBonusPledge(): ?float
    {
        return $this->bonusPledge;
    }

    public function setBonusPledge(float $bonusPledge): self
    {
        $this->bonusPledge = $bonusPledge;

        return $this;
    }

    public function getBonusStateSupport(): ?float
    {
        return $this->bonusStateSupport;
    }

    public function setBonusStateSupport(float $bonusStateSupport): self
    {
        $this->bonusStateSupport = $bonusStateSupport;

        return $this;
    }

    public function isIsIT(): ?bool
    {
        return $this->isIT;
    }

    public function setIsIT(bool $isIT): self
    {
        $this->isIT = $isIT;

        return $this;
    }

    public function getBonusProcentDinamic(): ?array
    {
        return $this->bonusProcentDinamic;
    }

    public function setBonusProcentDinamic(array $bonusProcentDinamic): self
    {
        $this->bonusProcentDinamic = $bonusProcentDinamic;

        return $this;
    }

    public function getBonusPledgeDinamic(): ?array
    {
        return $this->bonusPledgeDinamic;
    }

    public function setBonusPledgeDinamic(array $bonusPledgeDinamic): self
    {
        $this->bonusPledgeDinamic = $bonusPledgeDinamic;

        return $this;
    }

    public function getBonusStateSupportDinamic(): ?array
    {
        return $this->bonusStateSupportDinamic;
    }

    public function setBonusStateSupportDinamic(array $bonusStateSupportDinamic): self
    {
        $this->bonusStateSupportDinamic = $bonusStateSupportDinamic;

        return $this;
    }

    public function isStateIpotekaRefEnabled(): ?bool
    {
        return $this->stateIpotekaRefEnabled;
    }

    public function setStateIpotekaRefEnabled(bool $stateIpotekaRefEnabled): self
    {
        $this->stateIpotekaRefEnabled = $stateIpotekaRefEnabled;

        return $this;
    }

    public function isMarketIpotekaEnabled(): ?bool
    {
        return $this->marketIpotekaEnabled;
    }

    public function setMarketIpotekaEnabled(bool $marketIpotekaEnabled): self
    {
        $this->marketIpotekaEnabled = $marketIpotekaEnabled;

        return $this;
    }

    public function isMarketIpotekaRefEnabled(): ?bool
    {
        return $this->marketIpotekaRefEnabled;
    }

    public function setMarketIpotekaRefEnabled(bool $marketIpotekaRefEnabled): self
    {
        $this->marketIpotekaRefEnabled = $marketIpotekaRefEnabled;

        return $this;
    }

    public function isStateIpotekaEnabled(): ?bool
    {
        return $this->stateIpotekaEnabled;
    }

    public function setStateIpotekaEnabled(bool $stateIpotekaEnabled): self
    {
        $this->stateIpotekaEnabled = $stateIpotekaEnabled;

        return $this;
    }

    public function isMarketIpotekaPledgeEnabled(): ?bool
    {
        return $this->market_ipoteka_pledge_enabled;
    }

    public function setMarketIpotekaPledgeEnabled(bool $market_ipoteka_pledge_enabled): self
    {
        $this->market_ipoteka_pledge_enabled = $market_ipoteka_pledge_enabled;

        return $this;
    }

    public function isStateIpotekaPledgeEnabled(): ?bool
    {
        return $this->state_ipoteka_pledge_enabled;
    }

    public function setStateIpotekaPledgeEnabled(bool $state_ipoteka_pledge_enabled): self
    {
        $this->state_ipoteka_pledge_enabled = $state_ipoteka_pledge_enabled;

        return $this;
    }

    public function isProofMoney2docEnabled(): ?bool
    {
        return $this->proofMoney2docEnabled;
    }

    public function setProofMoney2docEnabled(bool $enabled): self
    {
        $this->proofMoney2docEnabled = $enabled;

        return $this;
    }

    public function isWithAddAmountEnabled(): ?bool
    {
        return $this->withAddAmountEnabled;
    }

    public function setWithAddAmountEnabled(bool $enabled): self
    {
        $this->withAddAmountEnabled = $enabled;

        return $this;
    }

    public function isWithConsolidationEnabled(): ?bool
    {
        return $this->withConsolidationEnabled;
    }

    public function setWithConsolidationEnabled(bool $enabled): self
    {
        $this->withConsolidationEnabled = $enabled;

        return $this;
    }

    /**
     * @return Collection<int, BankBonusType>
     */
    public function getBankBonusTypes(): Collection
    {
        return $this->bankBonusTypes;
    }

    public function addBankBonusType(BankBonusType $bankBonusType): self
    {
        if (!$this->bankBonusTypes->contains($bankBonusType)) {
            $this->bankBonusTypes[] = $bankBonusType;
            $bankBonusType->setBank($this);
        }

        return $this;
    }

    public function removeBankBonusType(BankBonusType $bankBonusType): self
    {
        if ($this->bankBonusTypes->removeElement($bankBonusType)) {
            // set the owning side to null (unless already changed)
            if ($bankBonusType->getBank() === $this) {
                $bankBonusType->setBank(null);
            }
        }

        return $this;
    }

    public function isProofMoney2docSelfEmployedEnabled(): ?bool
    {
        return $this->proofMoney2docSelfEmployedEnabled;
    }

    public function setProofMoney2docSelfEmployedEnabled(bool $proofMoney2docSelfEmployedEnabled): self
    {
        $this->proofMoney2docSelfEmployedEnabled = $proofMoney2docSelfEmployedEnabled;

        return $this;
    }

    public function isproofMoneySfrBusinessEnabled(): ?bool
    {
        return $this->proofMoneySfrBusinessEnabled;
    }

    public function setproofMoneySfrBusinessEnabled(bool $proofMoneySfrBusinessEnabled): self
    {
        $this->proofMoneySfrBusinessEnabled = $proofMoneySfrBusinessEnabled;

        return $this;
    }

    public function isProofMoney2ndflBusinessEnabled(): ?bool
    {
        return $this->proofMoney2ndflBusinessEnabled;
    }

    public function setProofMoney2ndflBusinessEnabled(bool $proofMoney2ndflBusinessEnabled): self
    {
        $this->proofMoney2ndflBusinessEnabled = $proofMoney2ndflBusinessEnabled;

        return $this;
    }

}
