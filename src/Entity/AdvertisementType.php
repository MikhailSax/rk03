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
