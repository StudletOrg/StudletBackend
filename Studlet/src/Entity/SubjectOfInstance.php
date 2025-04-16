<?php

namespace App\Entity;

use App\Repository\SubjectOfInstanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectOfInstanceRepository::class)]
class SubjectOfInstance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subjectOfInstances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coordinator = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Subject $subject = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'subjectOfIntance', orphanRemoval: true)]
    private Collection $groupss;

    public function __construct()
    {
        $this->groupss = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoordinator(): ?User
    {
        return $this->coordinator;
    }

    public function setCoordinator(?User $coordinator): static
    {
        $this->coordinator = $coordinator;

        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroupss(): Collection
    {
        return $this->groupss;
    }

    public function addGroupss(Group $groupss): static
    {
        if (!$this->groupss->contains($groupss)) {
            $this->groupss->add($groupss);
            $groupss->setSubjectOfIntance($this);
        }

        return $this;
    }

    public function removeGroupss(Group $groupss): static
    {
        if ($this->groupss->removeElement($groupss)) {
            // set the owning side to null (unless already changed)
            if ($groupss->getSubjectOfIntance() === $this) {
                $groupss->setSubjectOfIntance(null);
            }
        }

        return $this;
    }
}
