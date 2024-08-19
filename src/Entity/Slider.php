<?php

namespace App\Entity;

use App\Repository\SliderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SliderRepository::class)
 */
class Slider
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
    private $image_desktop;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $image_tablet;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $image_phone;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priority;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $href;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image_small_mobile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image_laptop;

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

    public function getImageDesktop(): ?string
    {
        return $this->image_desktop;
    }

    public function setImageDesktop(string $imageDesktop): self
    {
        $this->image_desktop = $imageDesktop;

        return $this;
    }

    public function getImageTablet(): ?string
    {
        return $this->image_tablet;
    }

    public function setImageTablet(string $imageTablet): self
    {
        $this->image_tablet = $imageTablet;

        return $this;
    }

    public function getImagePhone(): ?string
    {
        return $this->image_phone;
    }

    public function setImagePhone(string $imagePhone): self
    {
        $this->image_phone = $imagePhone;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function setHref(?string $href): self
    {
        $this->href = $href;

        return $this;
    }

    public function getImageSmallMobile(): ?string
    {
        return $this->image_small_mobile;
    }

    public function setImageSmallMobile(?string $image_small_mobile): self
    {
        $this->image_small_mobile = $image_small_mobile;

        return $this;
    }

    public function getImageLaptop(): ?string
    {
        return $this->image_laptop;
    }

    public function setImageLaptop(?string $image_laptop): self
    {
        $this->image_laptop = $image_laptop;

        return $this;
    }
}
