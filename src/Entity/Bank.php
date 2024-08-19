<?php

namespace App\Entity;

use App\Repository\BankRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BankRepository::class)
 */
class Bank
{

    const BITRIX_ID = [
        "БЖФ" => "",
        "СГБ" => 14464,
        "Ипотека24" => 14468,
        "Росбанк ДОМ" => 14466,
        "ЮниКредит Банк" => 14470,
        "Совкомбанк" => 14460,
        "Абсолют Банк" => 14458,
        "Транскапиталбанк" => 14462,
        "СКБ(ДОМ.РФ)" => 15670,
    ];

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
     * @Groups({"api_chat"})
     */
    private $title;

    /**
     * @ORM\Column(type="float")
     */
    private $bonusProcent = 0.6;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $creditTargets = [];
    //Может быть ["ипотека", "рефинансирование", "залог", "материнский"]

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $objectTypes = [];
    //Может быть ["квартира", "дом", "комната", "апартаменты"]

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $salerTypes = [];
    //Может быть ["физлицо", "застройщик", "юрлицо"]

    /**
     * @ORM\Column(type="boolean")
     */
    private $is2Doc = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is2DocUnresident = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is2DocRefinance = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isMotherCap = false;

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
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentStd;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentSocial;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentFamily;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procent2Doc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentHouse;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentRoom;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentWar;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentPledge;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $procentRefinance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $min;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $max;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minMSK;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxMSK;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minSoc;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxSoc;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minSocMSK;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxSocMSK;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $min2Doc;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $max2Doc;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $min2DocMSK;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $max2DocMSK;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstFlat;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstMother;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $first2DocFlat;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $first2DocUnresident;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $first2DocRefinance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstHome;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstPledge;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $firstRefinance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeMin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeMax;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeKNMin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeKNMax;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ageMin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ageMax;

    /**
     * @ORM\OneToMany(targetEntity=Calculated::class, mappedBy="bank")
     */
    private $calculateds;

    /**
     * @ORM\OneToOne(targetEntity=Attachment::class, cascade={"persist", "remove"})
     */
    private $logo;

    /**
     * @ORM\ManyToMany(targetEntity=Town::class, inversedBy="banks")
     */
    private $towns;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logopath;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $other = [];


    public function __toString()
    {
        return $this->id . "-" . $this->title;
    }


    public function __construct()
    {
        $this->calculateds = new ArrayCollection();
        $this->towns = new ArrayCollection();
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

    public function getCreditTargets(): ?array
    {
        return $this->creditTargets;
    }

    public function setCreditTargets(?array $creditTargets): self
    {
        $this->creditTargets = $creditTargets;

        return $this;
    }

    public function getObjectTypes(): ?array
    {
        return $this->objectTypes;
    }

    public function setObjectTypes(?array $objectTypes): self
    {
        $this->objectTypes = $objectTypes;

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

    public function getIs2Doc(): ?bool
    {
        return $this->is2Doc;
    }

    public function setIs2Doc(bool $is2Doc): self
    {
        $this->is2Doc = $is2Doc;

        return $this;
    }

    public function getIs2DocUnresident(): ?bool
    {
        return $this->is2DocUnresident;
    }

    public function setIs2DocUnresident(bool $is2DocUnresident): self
    {
        $this->is2DocUnresident = $is2DocUnresident;

        return $this;
    }

    public function getIs2DocRefinance(): ?bool
    {
        return $this->is2DocRefinance;
    }

    public function setIs2DocRefinance(bool $is2DocRefinance): self
    {
        $this->is2DocRefinance = $is2DocRefinance;

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

    public function getProcentStd(): ?float
    {
        return $this->procentStd;
    }

    public function setProcentStd(?float $procentStd): self
    {
        $this->procentStd = $procentStd;

        return $this;
    }

    public function getProcentSocial(): ?float
    {
        return $this->procentSocial;
    }

    public function setProcentSocial(?float $procentSocial): self
    {
        $this->procentSocial = $procentSocial;

        return $this;
    }

    public function getProcentFamily(): ?float
    {
        return $this->procentFamily;
    }

    public function setProcentFamily(?float $procentFamily): self
    {
        $this->procentFamily = $procentFamily;

        return $this;
    }

    public function getProcent2Doc(): ?float
    {
        return $this->procent2Doc;
    }

    public function setProcent2Doc(?float $procent2Doc): self
    {
        $this->procent2Doc = $procent2Doc;

        return $this;
    }

    public function getProcentHouse(): ?float
    {
        return $this->procentHouse;
    }

    public function setProcentHouse(?float $procentHouse): self
    {
        $this->procentHouse = $procentHouse;

        return $this;
    }

    public function getProcentRoom(): ?float
    {
        return $this->procentRoom;
    }

    public function setProcentRoom(?float $procentRoom): self
    {
        $this->procentRoom = $procentRoom;

        return $this;
    }

    public function getProcentWar(): ?float
    {
        return $this->procentWar;
    }

    public function setProcentWar(?float $procentWar): self
    {
        $this->procentWar = $procentWar;

        return $this;
    }

    public function getProcentPledge(): ?float
    {
        return $this->procentPledge;
    }

    public function setProcentPledge(float $procentPledge): self
    {
        $this->procentPledge = $procentPledge;

        return $this;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function setMin(?int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function setMax(?int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function getMinMSK(): ?int
    {
        return $this->minMSK;
    }

    public function setMinMSK(?int $minMSK): self
    {
        $this->minMSK = $minMSK;

        return $this;
    }

    public function getMaxMSK(): ?int
    {
        return $this->maxMSK;
    }

    public function setMaxMSK(?int $maxMSK): self
    {
        $this->maxMSK = $maxMSK;

        return $this;
    }

    public function getMinSoc(): ?int
    {
        return $this->minSoc;
    }

    public function setMinSoc(?int $minSoc): self
    {
        $this->minSoc = $minSoc;

        return $this;
    }

    public function getMaxSoc(): ?int
    {
        return $this->maxSoc;
    }

    public function setMaxSoc(?int $maxSoc): self
    {
        $this->maxSoc = $maxSoc;

        return $this;
    }

    public function getMinSocMSK(): ?int
    {
        return $this->minSocMSK;
    }

    public function setMinSocMSK(?int $minSocMSK): self
    {
        $this->minSocMSK = $minSocMSK;

        return $this;
    }

    public function getMaxSocMSK(): ?int
    {
        return $this->maxSocMSK;
    }

    public function setMaxSocMSK(?int $maxSocMSK): self
    {
        $this->maxSocMSK = $maxSocMSK;

        return $this;
    }

    public function getMin2Doc(): ?int
    {
        return $this->min2Doc;
    }

    public function setMin2Doc(?int $min2Doc): self
    {
        $this->min2Doc = $min2Doc;

        return $this;
    }

    public function getMax2Doc(): ?int
    {
        return $this->max2Doc;
    }

    public function setMax2Doc(?int $max2Doc): self
    {
        $this->max2Doc = $max2Doc;

        return $this;
    }

    public function getMin2DocMSK(): ?int
    {
        return $this->min2DocMSK;
    }

    public function setMin2DocMSK(?int $min2DocMSK): self
    {
        $this->min2DocMSK = $min2DocMSK;

        return $this;
    }

    public function getMax2DocMSK(): ?int
    {
        return $this->max2DocMSK;
    }

    public function setMax2DocMSK(?int $max2DocMSK): self
    {
        $this->max2DocMSK = $max2DocMSK;

        return $this;
    }

    public function getFirstFlat(): ?int
    {
        return $this->firstFlat;
    }

    public function setFirstFlat(?int $firstFlat): self
    {
        $this->firstFlat = $firstFlat;

        return $this;
    }

    public function getFirstMother(): ?int
    {
        return $this->firstMother;
    }

    public function setFirstMother(?int $firstMother): self
    {
        $this->firstMother = $firstMother;

        return $this;
    }

    public function getFirst2DocFlat(): ?int
    {
        return $this->first2DocFlat;
    }

    public function setFirst2DocFlat(?int $first2DocFlat): self
    {
        $this->first2DocFlat = $first2DocFlat;

        return $this;
    }

    public function getFirst2DocUnresident(): ?int
    {
        return $this->first2DocUnresident;
    }

    public function setFirst2DocUnresident(?int $first2DocUnresident): self
    {
        $this->first2DocUnresident = $first2DocUnresident;

        return $this;
    }

    public function getFirst2DocRefinance(): ?int
    {
        return $this->first2DocRefinance;
    }

    public function setFirst2DocRefinance(?int $first2DocRefinance): self
    {
        $this->first2DocRefinance = $first2DocRefinance;

        return $this;
    }

    public function getFirstHome(): ?int
    {
        return $this->firstHome;
    }

    public function setFirstHome(?int $firstHome): self
    {
        $this->firstHome = $firstHome;

        return $this;
    }

    public function getFirstPledge(): ?int
    {
        return $this->firstPledge;
    }

    public function setFirstPledge(?int $firstPledge): self
    {
        $this->firstPledge = $firstPledge;

        return $this;
    }

    public function getFirstRefinance(): ?int
    {
        return $this->firstRefinance;
    }

    public function setFirstRefinance(?int $firstRefinance): self
    {
        $this->firstRefinance = $firstRefinance;

        return $this;
    }

    public function getTimeMin(): ?int
    {
        return $this->timeMin;
    }

    public function setTimeMin(?int $timeMin): self
    {
        $this->timeMin = $timeMin;

        return $this;
    }

    public function getTimeKNMin(): ?int
    {
        return $this->timeKNMin;
    }

    public function setTimeKNMin(?int $timeKNMin): self
    {
        $this->timeKNMin = $timeKNMin;

        return $this;
    }

    public function getTimeMax(): ?int
    {
        return $this->timeMax;
    }

    public function setTimeMax(?int $timeMax): self
    {
        $this->timeMax = $timeMax;

        return $this;
    }

    public function getTimeKNMax(): ?int
    {
        return $this->timeKNMax;
    }

    public function setTimeKNMax(?int $timeKNMax): self
    {
        $this->timeKNMax = $timeKNMax;

        return $this;
    }

    public function getAgeMin(): ?int
    {
        return $this->ageMin;
    }

    public function setAgeMin(?int $ageMin): self
    {
        $this->ageMin = $ageMin;

        return $this;
    }

    public function getAgeMax(): ?int
    {
        return $this->ageMax;
    }

    public function setAgeMax(?int $ageMax): self
    {
        $this->ageMax = $ageMax;

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
            $calculated->setBank($this);
        }

        return $this;
    }

    public function removeCalculated(Calculated $calculated): self
    {
        if ($this->calculateds->removeElement($calculated)) {
            // set the owning side to null (unless already changed)
            if ($calculated->getBank() === $this) {
                $calculated->setBank(null);
            }
        }

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

    public function getLogo(): ?Attachment
    {
        return $this->logo;
    }

    public function setLogo(?Attachment $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getProcentRefinance(): ?float
    {
        return $this->procentRefinance;
    }

    public function setProcentRefinance(?float $procentRefinance): self
    {
        $this->procentRefinance = $procentRefinance;

        return $this;
    }

    /**
     * @return Collection|Town[]
     */
    public function getTowns(): Collection
    {
        return $this->towns;
    }

    public function addTown(Town $town): self
    {
        if (!$this->towns->contains($town)) {
            $this->towns[] = $town;
        }

        return $this;
    }

    public function removeTown(Town $town): self
    {
        $this->towns->removeElement($town);

        return $this;
    }

    public function getLogopath(): ?string
    {
        return $this->logopath;
    }

    public function setLogopath(?string $logopath): self
    {
        $this->logopath = $logopath;

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

    public function getOther(): ?array
    {
        return $this->other;
    }

    public function setOther(?array $other): self
    {
        $this->other = $other;

        return $this;
    }

    public function isIs2Doc(): ?bool
    {
        return $this->is2Doc;
    }

    public function isIs2DocUnresident(): ?bool
    {
        return $this->is2DocUnresident;
    }

    public function isIs2DocRefinance(): ?bool
    {
        return $this->is2DocRefinance;
    }

    public function isIsMotherCap(): ?bool
    {
        return $this->isMotherCap;
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
}
