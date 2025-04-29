<?php

namespace App\Controller;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Repository\SubjectOfInstanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ApiGroupController extends AbstractController{
    #[Route('/groups/{id}/students', name: 'group_students', methods: ['GET'])]
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

    #[Route('/groups/{id}', name: 'group_details', methods: ['GET'])]
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



}
