<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GradeRepository;
use App\Repository\UserRepository;
use App\Repository\GroupRepository;
use App\Entity\User;
use App\Entity\Grade;
use App\Entity\Group;

final class ApiGradeController extends AbstractController
{
    #[Route('/student/{id}/allgrades', name: 'api_student_all_grades', methods: ['GET'])]
    public function getGradesByStudent(int $id, GradeRepository $gradeRepository, UserRepository $userRepository): JsonResponse
    {
        $student = $userRepository->findBy(['id' => $id]);

        if (!$student) {
            throw new NotFoundHttpException('Student not found.');
        }

        $grades = $gradeRepository->findBy(['student' => $id]);
        $data = [];

        foreach ($grades as $grade) {
            $data[] = [
                'id' => $grade->getId(),
                'value' => $grade->getValue(),
                'group' => $grade->getGroupp()->getId(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/student/{id}/subject/{subject_id}/grades', name: 'api_student_subject_grades', methods: ['GET'])]
    public function getGradesByStudentAndSubject(int $id, int $subject_id, GradeRepository $gradeRepository, UserRepository $userRepository, GroupRepository $groupRepository): JsonResponse
    {
        $student = $userRepository->findBy(['id' => $id]);
        if (!$student) {
            throw new NotFoundHttpException('Student not found.');
        }

        $group = $groupRepository->findBy(['id' => $subject_id]);
        if (!$group) {
            throw new NotFoundHttpException('Subject not found.');
        }

        $grades = $gradeRepository->findBy(['student' => $id, 'groupp' => $subject_id]);

        $data = [];

        foreach ($grades as $grade) {
            $data[] = [
                'id' => $grade->getId(),
                'value' => $grade->getValue(),
                'group' => $grade->getGroupp()->getId(),
            ];
        }

        return $this->json($data);
    }
}
