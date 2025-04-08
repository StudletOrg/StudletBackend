<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    /**
     * @var Collection<int, SubjectOfInstance>
     */
    #[ORM\OneToMany(targetEntity: SubjectOfInstance::class, mappedBy: 'Coordinator')]
    private Collection $subjectOfInstances;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'professor')]
    private Collection $groupsOfProfessor;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'students')]
    private Collection $groupsOfStudents;

    /**
     * @var Collection<int, Grade>
     */
    #[ORM\OneToMany(targetEntity: Grade::class, mappedBy: 'student', orphanRemoval: true)]
    private Collection $grades;

    public function __construct()
    {
        $this->subjectOfInstances = new ArrayCollection();
        $this->groupsOfProfessor = new ArrayCollection();
        $this->groupsOfStudents = new ArrayCollection();
        $this->grades = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return Collection<int, SubjectOfInstance>
     */
    public function getSubjectOfInstances(): Collection
    {
        return $this->subjectOfInstances;
    }

    public function addSubjectOfInstance(SubjectOfInstance $subjectOfInstance): static
    {
        if (!$this->subjectOfInstances->contains($subjectOfInstance)) {
            $this->subjectOfInstances->add($subjectOfInstance);
            $subjectOfInstance->setCoordinator($this);
        }

        return $this;
    }

    public function removeSubjectOfInstance(SubjectOfInstance $subjectOfInstance): static
    {
        if ($this->subjectOfInstances->removeElement($subjectOfInstance)) {
            // set the owning side to null (unless already changed)
            if ($subjectOfInstance->getCoordinator() === $this) {
                $subjectOfInstance->setCoordinator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroupsOfProfessor(): Collection
    {
        return $this->groupsOfProfessor;
    }

    public function addGroupsOfProfessor(Group $groupsOfProfessor): static
    {
        if (!$this->groupsOfProfessor->contains($groupsOfProfessor)) {
            $this->groupsOfProfessor->add($groupsOfProfessor);
            $groupsOfProfessor->setProfessor($this);
        }

        return $this;
    }

    public function removeGroupsOfProfessor(Group $groupsOfProfessor): static
    {
        if ($this->groupsOfProfessor->removeElement($groupsOfProfessor)) {
            // set the owning side to null (unless already changed)
            if ($groupsOfProfessor->getProfessor() === $this) {
                $groupsOfProfessor->setProfessor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroupsOfStudents(): Collection
    {
        return $this->groupsOfStudents;
    }

    public function addGroupsOfStudent(Group $groupsOfStudent): static
    {
        if (!$this->groupsOfStudents->contains($groupsOfStudent)) {
            $this->groupsOfStudents->add($groupsOfStudent);
            $groupsOfStudent->addStudent($this);
        }

        return $this;
    }

    public function removeGroupsOfStudent(Group $groupsOfStudent): static
    {
        if ($this->groupsOfStudents->removeElement($groupsOfStudent)) {
            $groupsOfStudent->removeStudent($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Grade>
     */
    public function getGrades(): Collection
    {
        return $this->grades;
    }

    public function addGrade(Grade $grade): static
    {
        if (!$this->grades->contains($grade)) {
            $this->grades->add($grade);
            $grade->setStudent($this);
        }

        return $this;
    }

    public function removeGrade(Grade $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getStudent() === $this) {
                $grade->setStudent(null);
            }
        }

        return $this;
    }
}
