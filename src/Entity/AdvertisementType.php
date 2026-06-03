<?php

namespace App\Entity;

use App\Repository\AdvertisementTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementTypeRepository::class)]
class AdvertisementType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: AdvertisementCategory::class, inversedBy: 'types')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AdvertisementCategory $category = null;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Advertisement::class)]
    private Collection $advertisements;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $sourceRef = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $sourceData = null;

    public function __construct()
    {
        $this->advertisements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCategory(): ?AdvertisementCategory
    {
        return $this->category;
    }

    public function setCategory(?AdvertisementCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getSourceRef(): ?string
    {
        return $this->sourceRef;
    }

    public function setSourceRef(?string $sourceRef): self
    {
        $this->sourceRef = $sourceRef === null ? null : trim($sourceRef);

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSourceData(): ?array
    {
        return $this->sourceData;
    }

    /**
     * @param array<string, mixed>|null $sourceData
     */
    public function setSourceData(?array $sourceData): self
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    /** @return Collection<int, Advertisement> */
    public function getAdvertisements(): Collection
    {
        return $this->advertisements;
    }


    public function __toString(): string
    {
        return $this->name;
    }
}
