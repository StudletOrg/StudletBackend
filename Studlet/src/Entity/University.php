<?php

namespace App\Entity;

use App\Repository\UniversityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniversityRepository::class)]
class University
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    /**
     * @var Collection<int, FieldOfStudy>
     */
    #[ORM\OneToMany(targetEntity: FieldOfStudy::class, mappedBy: 'university', orphanRemoval: true)]
    private Collection $fieldOfStudies;

    public function __construct()
    {
        $this->fieldOfStudies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, FieldOfStudy>
     */
    public function getFieldOfStudies(): Collection
    {
        return $this->fieldOfStudies;
    }

    public function addFieldOfStudy(FieldOfStudy $fieldOfStudy): static
    {
        if (!$this->fieldOfStudies->contains($fieldOfStudy)) {
            $this->fieldOfStudies->add($fieldOfStudy);
            $fieldOfStudy->setUniversity($this);
        }

        return $this;
    }

    public function removeFieldOfStudy(FieldOfStudy $fieldOfStudy): static
    {
        if ($this->fieldOfStudies->removeElement($fieldOfStudy)) {
            // set the owning side to null (unless already changed)
            if ($fieldOfStudy->getUniversity() === $this) {
                $fieldOfStudy->setUniversity(null);
            }
        }

        return $this;
    }
}
