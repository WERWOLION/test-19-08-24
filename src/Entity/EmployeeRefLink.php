<?php

namespace App\Entity;

use App\Repository\EmployeeRefLinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmployeeRefLinkRepository::class)
 */
class EmployeeRefLink
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
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    /**
     * @ORM\Column(type="integer")
     */
    private $bitrix_id;

    private $fullHref;

    /**
     * @ORM\OneToMany(targetEntity=PreUser::class, mappedBy="employeeRefLink")
     */
    private $preUsers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $agentId;

    public function __construct()
    {
        $this->preUsers = new ArrayCollection();
    }

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

    public function getHash(): ?string
    {
        if ($this->hash === null) {
            $hash = md5(random_int(0, 999999));
            $this->setHash($hash);
            return $hash;
        }

        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getBitrixId(): ?int
    {
        return $this->bitrix_id;
    }

    public function setBitrixId(int $bitrix_id): self
    {
        $this->bitrix_id = $bitrix_id;

        return $this;
    }

    public function getFullHref(): ?string
    {
        return "https://lk.ipoteka.life/register?ref_hash={$this->getHash()}";
    }

    /**
     * @return Collection<int, PreUser>
     */
    public function getPreUsers(): Collection
    {
        return $this->preUsers;
    }

    public function addPreUser(PreUser $preUser): self
    {
        if (!$this->preUsers->contains($preUser)) {
            $this->preUsers[] = $preUser;
            $preUser->setEmployeeRefLink($this);
        }

        return $this;
    }

    public function removePreUser(PreUser $preUser): self
    {
        if ($this->preUsers->removeElement($preUser)) {
            // set the owning side to null (unless already changed)
            if ($preUser->getEmployeeRefLink() === $this) {
                $preUser->setEmployeeRefLink(null);
            }
        }

        return $this;
    }

    public function getAgentId(): ?int
    {
        return $this->agentId;
    }

    public function setAgentId(?int $agentId): self
    {
        $this->agentId = $agentId;

        return $this;
    }
}
