<?php

namespace App\Entity;

use App\Repository\AdvertisementLocationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementLocationRepository::class)]
class AdvertisementLocation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $azimuth = null;

    #[ORM\OneToOne(inversedBy: 'location', targetEntity: Advertisement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Advertisement $advertisement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getAzimuth(): ?int
    {
        return $this->azimuth;
    }

    public function setAzimuth(?int $azimuth): self
    {
        $this->azimuth = $azimuth;
        return $this;
    }

    public function getAdvertisement(): ?Advertisement
    {
        return $this->advertisement;
    }

    public function setAdvertisement(?Advertisement $advertisement): self
    {
        $this->advertisement = $advertisement;
        return $this;
    }
}
