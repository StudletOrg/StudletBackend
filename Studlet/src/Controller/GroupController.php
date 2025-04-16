<?php

namespace App\Controller;

use App\Repository\GroupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GroupController extends AbstractController{
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
}
