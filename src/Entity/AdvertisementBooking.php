<?php

namespace App\Entity;

use App\Repository\AdvertisementBookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementBookingRepository::class)]
class AdvertisementBooking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Advertisement $advertisement = null;

    #[ORM\Column(length: 10)]
    private ?string $sideCode = null;

    #[ORM\Column(length: 255)]
    private ?string $clientName = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $orderRef = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdvertisement(): ?Advertisement
    {
        return $this->advertisement;
    }

    public function setAdvertisement(?Advertisement $advertisement): static
    {
        $this->advertisement = $advertisement;

        return $this;
    }

    public function getSideCode(): ?string
    {
        return $this->sideCode;
    }

    public function setSideCode(string $sideCode): static
    {
        $this->sideCode = mb_strtoupper(trim($sideCode));

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): static
    {
        $this->clientName = trim($clientName);

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }


    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;

        return $this;
    }
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment === null ? null : trim($comment);

        return $this;
    }
}
