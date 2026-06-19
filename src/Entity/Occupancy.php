<?php

namespace App\Entity;

use App\Repository\OccupancyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Занятость рекламной стороны по месяцам — импортируется из 1С (тег <Row>).
 *
 * Status: 0 — свободно, 1 — занято, 3 — бронь.
 */
#[ORM\Entity(repositoryClass: OccupancyRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_occupancy_side_month', columns: ['advertisement_side_id', 'month'])]
#[ORM\Index(columns: ['month'], name: 'idx_occupancy_month')]
class Occupancy
{
    public const STATUS_FREE = 0;
    public const STATUS_BUSY = 1;
    public const STATUS_RESERVED = 3;

    public const STATUS_LABELS = [
        self::STATUS_FREE => 'Свободно',
        self::STATUS_BUSY => 'Занято',
        self::STATUS_RESERVED => 'Бронь',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Ссылка на сторону конструкции (AdvertisementSide) */
    #[ORM\ManyToOne(targetEntity: AdvertisementSide::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AdvertisementSide $advertisementSide = null;

    /** Первый день месяца (например, 2026-07-01) */
    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $month = null;

    /** 0 = свободно, 1 = занято, 3 = бронь */
    #[ORM\Column(type: 'smallint')]
    private int $status = self::STATUS_FREE;

    /** UUID записи из 1С (поле <ID> в <Row>) */
    #[ORM\Column(length: 36, nullable: true)]
    private ?string $sourceId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdvertisementSide(): ?AdvertisementSide
    {
        return $this->advertisementSide;
    }

    public function setAdvertisementSide(?AdvertisementSide $side): static
    {
        $this->advertisementSide = $side;
        return $this;
    }

    public function getMonth(): ?\DateTimeImmutable
    {
        return $this->month;
    }

    public function setMonth(\DateTimeImmutable $month): static
    {
        $this->month = $month;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Неизвестно';
    }

    public function isFree(): bool
    {
        return $this->status === self::STATUS_FREE;
    }

    public function isBusy(): bool
    {
        return $this->status === self::STATUS_BUSY;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $id): static
    {
        $this->sourceId = $id;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $dt): static
    {
        $this->updatedAt = $dt;
        return $this;
    }
}
