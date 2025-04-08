<?php

namespace App\Entity;

use App\Repository\SubjectOfInstanceRepository;
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
}
