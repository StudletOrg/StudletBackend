<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[Groups(['group_list'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['group_list'])]
    #[ORM\Column]
    private ?int $numer = null;

    #[ORM\ManyToOne(inversedBy: 'groupsOfProfessor')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $professor = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'groupsOfStudents')]
    private Collection $students;

    /**
     * @var Collection<int, Grade>
     */
    #[ORM\OneToMany(targetEntity: Grade::class, mappedBy: 'groupp', orphanRemoval: true)]
    private Collection $grades;

    #[Groups(['group_list'])]
    #[ORM\ManyToOne(inversedBy: 'groupss')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubjectOfInstance $subjectOfIntance = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'relatedGroup', orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, Attendance>
     */
    #[ORM\OneToMany(targetEntity: Attendance::class, mappedBy: 'groupp', orphanRemoval: true)]
    private Collection $attendances;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->grades = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->attendances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumer(): ?int
    {
        return $this->numer;
    }

    public function setNumer(int $numer): static
    {
        $this->numer = $numer;

        return $this;
    }

    public function getProfessor(): ?User
    {
        return $this->professor;
    }

    public function setProfessor(?User $professor): static
    {
        $this->professor = $professor;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
        }

        return $this;
    }

    public function removeStudent(User $student): static
    {
        $this->students->removeElement($student);

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
            $grade->setGroupp($this);
        }

        return $this;
    }

    public function removeGrade(Grade $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            if ($grade->getGroupp() === $this) {
                $grade->setGroupp(null);
            }
        }

        return $this;
    }

    public function getSubjectOfIntance(): ?SubjectOfInstance
    {
        return $this->subjectOfIntance;
    }

    public function setSubjectOfIntance(?SubjectOfInstance $subjectOfIntance): static
    {
        $this->subjectOfIntance = $subjectOfIntance;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setRelatedGroup($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getRelatedGroup() === $this) {
                $note->setRelatedGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Attendance>
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function addAttendance(Attendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setGroupp($this);
        }

        return $this;
    }

    public function removeAttendance(Attendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getGroupp() === $this) {
                $attendance->setGroupp(null);
            }
        }

        return $this;
    }
}
