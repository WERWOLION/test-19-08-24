<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Serializable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Repository\AttachmentRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass=AttachmentRepository::class)
 * @Vich\Uploadable
 */
class Attachment implements Serializable
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
    private $fileName;

    /**
     * @Vich\UploadableField(mapping="offerdocs", fileNameProperty="filename")
     */
    private $file;

    /**
     * @ORM\ManyToOne(targetEntity=Offer::class, inversedBy="documents")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $offer;

    /**
     * @ORM\ManyToOne(targetEntity=Partner::class, inversedBy="documents")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $partnerlink;

    /**
     * @ORM\ManyToOne(targetEntity=Calculated::class, inversedBy="objectDocs")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $calculated;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="attachments")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $foldername;


    public function __toString()
    {
        $res = $this->id . " / ";
        if($this->description){
            $res .= $this->description;
        } else {
            $res .= $this->fileName;
        }
        return $res;
    }

    public function __clone()
    {
        $this->id = null;
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

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setFile(?File $imageFile = null): void
    {
        $this->file = $imageFile;
        if ($imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
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

    public function getPartnerlink(): ?Partner
    {
        return $this->partnerlink;
    }

    public function setPartnerlink(?Partner $partnerlink): self
    {
        $this->partnerlink = $partnerlink;

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


    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->fileName,
            $this->description,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->fileName,
            $this->description,
        ) = unserialize($serialized, array('allowed_classes' => false));
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getFoldername(): ?string
    {
        return $this->foldername;
    }

    public function setFoldername(string $foldername): self
    {
        $this->foldername = $foldername;

        return $this;
    }
}
