<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Note;
use App\Entity\Attendance;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Repository\AttendanceRepository;
use App\Repository\SubjectOfInstanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * Kontroler zarządzający grupami studentów.
 *
 */
final class ApiGroupController extends AbstractController{
    #[Route('/api/groups/{id}/students', name: 'group_students', methods: ['GET'])]
    public function getGroupStudents(int $id, GroupRepository $groupRepository): JsonResponse
    {
        $group = $groupRepository->find($id);

        if (!$group) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        $students = $group->getStudents()->map(function ($student) {
            return [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
                'lastName' => $student->getLastName(),
                'email' => $student->getEmail(),
            ];
        });
        return $this->json($students);
    }

    #[Route('/api/groups/{id}/studentsWithGrades', name: 'group_students_with_grades', methods: ['GET'])]
    public function getGroupStudentsWithGrades(int $id, GroupRepository $groupRepository, AttendanceRepository $attendanceRepository): JsonResponse {
        $group = $groupRepository->find($id);

        if (!$group) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        $students = $group->getStudents()->map(function ($student) use ($id, $attendanceRepository, $group) {
            $attendance = $attendanceRepository->findOneBy([
                'user' => $student,
                'groupp' => $group,
            ]);

            return [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
                'lastName' => $student->getLastName(),
                'email' => $student->getEmail(),
                'grades' => array_values(
                    $student->getGrades()
                        ->filter(fn($grade) => $grade->getGroupp()?->getId() === $id)
                        ->map(function ($grade) {
                            return [
                                'id' => $grade->getId(),
                                'value' => $grade->getValue(),
                                'subject' => $grade->getGroupp()
                                    ?->getSubjectOfIntance()
                                    ?->getSubject()
                                    ?->getName(),
                            ];
                        })
                        ->toArray()
                ),
                'attendance' => $attendance ? [
                    'value' => $attendance->getValue(),
                    'maksvalue' => $attendance->getMaksvalue(),
                ] : null,
            ];
        })->toArray();

        return $this->json($students);
    }


    #[Route('/api/groups/student-groups', name: 'student_groups_api', methods: ['GET'])]
    public function getStudentGroupsApi(AttendanceRepository $attendanceRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $groups = $user->getGroupsOfStudents();

        $result = [];

        foreach ($groups as $group) {
            $subjectInstance = $group->getSubjectOfIntance();
            $subjectName = $subjectInstance?->getSubject()?->getName();
            $professor = $group->getProfessor();

            $attendance = $attendanceRepository->findOneBy([
                'user' => $user,
                'groupp' => $group,
            ]);

            $attendanceData = $attendance ? [
                'value' => $attendance->getValue(),
                'maksvalue' => $attendance->getMaksvalue(),
            ] : null;

            $result[] = [
                'groupNumber' => $group->getNumer(),
                'professor' => [
                    'firstName' => $professor?->getFirstName(),
                    'lastName' => $professor?->getLastName(),
                    'email' => $professor?->getEmail(),
                ],
                'studentCount' => $group->getStudents()->count(),
                'subject' => $subjectName ?? 'Brak powiązanego przedmiotu',
                'attendance' => $attendanceData,
            ];
        }
        return $this->json($result);
    }

    #[Route('/api/groups/{id}', name: 'group_details', methods: ['GET'])]
    public function getGroupDetails(int $id, GroupRepository $groupRepository): JsonResponse
    {
        $group = $groupRepository->find($id);

        if (!$group) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        $professor = $group->getProfessor();
        $studentCount = $group->getStudents()->count();

        $subjectInstance = $group->getSubjectOfIntance();
        $subjectName = $subjectInstance?->getSubject()?->getName();

        $notes = [];
        foreach ($group->getNotes() as $note) {
            $notes[] = [
                'id' => $note->getId(),
                'title' => $note->getTitle(),
                'creationDate' => $note->getCreationDate()->format('Y-m-d H:i:s'),
                'author' => [
                    'id' => $note->getAuthor()->getId(),
                    'firstName' => $note->getAuthor()->getFirstName(),
                    'lastName' => $note->getAuthor()->getLastName(),
                ],
                'content' => $note->getContent(),
            ];
        }

        return $this->json([
            'groupId' => $group->getId(),
            'groupNumber' => $group->getNumer(),
            'professor' => [
                'firstName' => $professor?->getFirstName(),
                'lastName' => $professor?->getLastName(),
                'email' => $professor?->getEmail(),
            ],
            'studentCount' => $studentCount,
            'subject' => $subjectName ?? 'Brak powiązanego przedmiotu',
            'notes' => $notes,
        ]);
    }

    #[Route('api/groups', name: 'api_create_group', methods: ['POST'])]
    public function createGroup(Request $request, EntityManagerInterface $em, SubjectOfInstanceRepository $subjectRepo, UserRepository $studentRepo): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu – użytkownik niezalogowany.');
        }
        if (!in_array('ROLE_PROFESSOR', $user->getRoles())) {
            throw new AccessDeniedHttpException('Nie jesteś procesorem!');
        }
        
        $data = json_decode($request->getContent(), true);
        if (!isset($data['numer'], $data['subjectInstanceId'])) {
            return $this->json(['error' => 'Missing data'], 400);
        }

        $subjectInstance = $subjectRepo->find($data['subjectInstanceId']);
        if (!$subjectInstance) {
            return $this->json(['error' => 'SubjectOfInstance not found'], 404);
        }

        $group = new Group();
        $group->setNumer($data['numer']);
        $group->setProfessor($user);
        $group->setSubjectOfIntance($subjectInstance);

        if (!empty($data['studentIds']) && is_array($data['studentIds'])) {
            foreach ($data['studentIds'] as $studentId) {
                $student = $studentRepo->find($studentId);
                if ($student) {
                    $group->addStudent($student);

                    $existingAttendance = $em->getRepository(Attendance::class)->findOneBy([
                        'user' => $student,
                        'groupp' => $group,
                    ]);

                    if (!$existingAttendance) {
                        $attendance = new Attendance();
                        $attendance->setUser($student);
                        $attendance->setGroupp($group);
                        $attendance->setMaksvalue(30);
                        $attendance->setValue(0);
                        $em->persist($attendance);
                    }
                }
            }
        }

        $em->persist($group);
        $em->flush();

        return $this->json([
            'success' => true,
            'groupId' => $group->getId(),
            'message' => 'Group created successfully'
        ], 201);
    }

    #[Route('api/groups/{id}/students/add', name: 'add_students_to_group', methods: ['POST'])]
    public function addStudentsToGroup(int $id, Request $request, EntityManagerInterface $em, GroupRepository $groupRepository, UserRepository $studentRepo): JsonResponse {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_PROFESSOR', $user->getRoles())) {
            throw new AccessDeniedHttpException('Dostęp tylko dla procesorów.');
        }

        $group = $groupRepository->find($id);
        if (!$group) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        if ($group->getProfessor() !== $user) {
            throw new AccessDeniedHttpException('Nie jesteś właścicielem tej grupy.');
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['studentIds']) || !is_array($data['studentIds'])) {
            return $this->json(['error' => 'Missing or invalid studentIds'], 400);
        }

        foreach ($data['studentIds'] as $studentId) {
            $student = $studentRepo->find($studentId);
            if ($student) {
                $group->addStudent($student);

                $existingAttendance = $em->getRepository(Attendance::class)->findOneBy([
                    'user' => $student,
                    'groupp' => $group,
                ]);

                if (!$existingAttendance) {
                    $attendance = new Attendance();
                    $attendance->setUser($student);
                    $attendance->setGroupp($group);
                    $attendance->setMaksvalue(30);
                    $attendance->setValue(0);
                    $em->persist($attendance);
                }
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Students added successfully.'
        ]);
    }

    #[Route('api/groups/{groupId}/student/{studentId}', name: 'remove_student_from_group', methods: ['DELETE'])]
    public function removeStudentFromGroup(int $groupId, int $studentId, EntityManagerInterface $em, GroupRepository $groupRepository, UserRepository $studentRepo): JsonResponse {

        $user = $this->getUser();
        if (!$user || !in_array('ROLE_PROFESSOR', $user->getRoles())) {
            throw new AccessDeniedHttpException('Dostęp tylko dla profesorów.');
        }

        $group = $groupRepository->find($groupId);
        if (!$group) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        if ($group->getProfessor() !== $user) {
            throw new AccessDeniedHttpException('Nie jesteś właścicielem tej grupy.');
        }

        $student = $studentRepo -> find($studentId);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        if (!$group->getStudents()->contains($student)) {
            return $this->json(['error' => 'Student is not in this group'], 400);
        }

        $group->removeStudent($student);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Student removed from group.'
        ]);
    }

    #[Route('/api/notes/new', name: 'create_note', methods: ['POST'])]
    public function createNote(Request $request, GroupRepository $groupRepository, EntityManagerInterface $entityManager): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'], $data['groupId'])) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $group = $groupRepository->find($data['groupId']);
        if (!$group) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        $note = new Note();
        $note->setTitle($data['title']);
        $note->setContent($data['content'] ?? null);
        $note->setCreationDate(new \DateTime());
        $note->setAuthor($user);
        $note->setRelatedGroup($group);

        $entityManager->persist($note);
        $entityManager->flush();

        return $this->json([
            'message' => 'Note created successfully',
            'noteId' => $note->getId(),
        ], 201);
    }

    #[Route('/api/field/{id}/students', name: 'students_by_field', methods: ['GET'])]
    public function studentsByField(int $id, UserRepository $repo): JsonResponse
    {
        $students = $repo->findBy(['fieldOfStudy' => $id]);

        $data = array_map(fn($student) => [
            'id' => $student->getId(),
            'firstname' => $student->getFirstName(),
            'lastname' => $student->getLastName(),
            'email' => $student->getEmail(),
        ], $students);

        return $this->json($data);
    }

    #[Route('/api/field/{fieldId}/group/{groupId}/students', name: 'students_by_field_not_in_group', methods: ['GET'])]
    public function studentsByFieldNotInGroup(int $fieldId, int $groupId, UserRepository $userRepo, GroupRepository $groupRepo): JsonResponse
    {
        $group = $groupRepo->find($groupId);
        if (!$group) {
            return $this->json(['error' => 'Grupa nie istnieje'], 404);
        }

        $studentsByField = $userRepo->findBy(['fieldOfStudy' => $fieldId]);
        $studentsInGroup = $group->getStudents();
        $studentsInGroupIds = array_map(fn($student) => $student->getId(), $studentsInGroup->toArray());
        $filteredStudents = array_filter($studentsByField, function($student) use ($studentsInGroupIds) {
            $roles = $student->getRoles();
            $isProcessor = in_array('ROLE_PROFESSOR', $roles);

            return !in_array($student->getId(), $studentsInGroupIds) && !$isProcessor;
        });
        $filteredStudents = array_values($filteredStudents);
        $data = array_map(fn($student) => [
            'id' => $student->getId(),
            'firstname' => $student->getFirstName(),
            'lastname' => $student->getLastName(),
            'email' => $student->getEmail(),
        ], $filteredStudents);

        return $this->json($data);
    }
}
