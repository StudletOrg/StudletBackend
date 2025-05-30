<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GradeRepository;
use App\Repository\UserRepository;
use App\Repository\GroupRepository;
use App\Entity\User;
use App\Entity\Grade;
use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


final class ApiGradeController extends AbstractController
{
    #[Route('/api/student/{id}/allgrades', name: 'api_student_all_grades', methods: ['GET'])]
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
                'group' => $grade->getGroupp()->getSubjectOfIntance()->getSubject()->getName()." - grupa ".$grade->getGroupp()->getId(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/student/mygrades', name: 'api_student_my_grades', methods: ['GET'])]
    public function getMyGrades(GradeRepository $gradeRepository, UserRepository $userRepository): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu – użytkownik niezalogowany.');
        }

        $grades = $gradeRepository->findBy(
            ['student' => $user],
            ['dateOfCreation' => 'DESC'],
            5
        );

        $data = [];

        foreach ($grades as $grade) {
            $data[] = [
                'id' => $grade->getId(),
                'grade' => $grade->getValue(),
                'subject' => $grade->getGroupp()->getSubjectOfIntance()->getSubject()->getName(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/student/mysubjects', name: 'api_student_my_subjects', methods: ['GET'])]
    public function getMySubjects(GradeRepository $gradeRepository, UserRepository $userRepository): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu – użytkownik niezalogowany.');
        }

        $subjects = [];
        foreach ($user->getGroupsOfStudents() as $group) {
            $subject = $group->getSubjectOfIntance()->getSubject();
            $subjects[$subject->getId()] = [
                'id' => $subject->getId(),
                'nazwa' => $subject->getName(),
            ];
        }

        return $this->json(array_values($subjects));
    }

    #[Route('/api/student/{id}/subject/{subject_id}/grades', name: 'api_student_subject_grades', methods: ['GET'])]
    public function getGradesByStudentAndSubject(int $id, int $subject_id, GradeRepository $gradeRepository, UserRepository $userRepository, GroupRepository $groupRepository): JsonResponse
    {
        $student;
        if($id == -1){
            $student = $this->getUser();
        }
        else{
            $student = $userRepository->findBy(['id' => $id]);
        }
        if (!$student) {
            throw new NotFoundHttpException('Student not found.');
        }

        $group = $groupRepository->findBy(['id' => $subject_id]);
        if (!$group) {
            throw new NotFoundHttpException('Subject not found.');
        }

        $grades = $gradeRepository->findBy(['student' => $student->getId(), 'groupp' => $subject_id]);

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

    #[Route('/api/grades/add', name: 'api_grade_add', methods: ['POST'])]
    public function addGrade(Request $request, EntityManagerInterface $em, UserRepository $userRepo, GroupRepository $groupRepo): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu – użytkownik niezalogowany.');
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['studentId'], $data['groupId'], $data['value'])) {
            return $this->json(['error' => 'Brakuje wymaganych danych.'], 400);
        }

        $group = $groupRepo->find($data['groupId']);
        if (!$group) {
            return $this->json(['error' => 'Grupa nie istnieje.'], 404);
        }

        // Sprawdzenie, czy użytkownik to profesor tej grupy
        if ($group->getProfessor()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Nie jesteś profesorem tej grupy.');
        }

        $student = $userRepo->find($data['studentId']);
        if (!$student) {
            return $this->json(['error' => 'Student nie istnieje.'], 404);
        }

        // Opcjonalnie: sprawdź, czy student należy do tej grupy
        if (!$group->getStudents()->contains($student)) {
            return $this->json(['error' => 'Student nie należy do tej grupy.'], 400);
        }

        $grade = new Grade();
        $grade->setValue($data['value']);
        $grade->setStudent($student);
        $grade->setGroupp($group);
        $grade->setDateOfCreation(new \DateTimeImmutable());

        $em->persist($grade);
        $em->flush();

        return $this->json([
            'success' => true,
            'gradeId' => $grade->getId(),
            'message' => 'Ocena została dodana pomyślnie.'
        ], 201);
    }

    #[Route('/api/grade/{id}', name: 'api_grade_delete', methods: ['DELETE'])]
    public function deleteGrade(int $id, GradeRepository $gradeRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu – użytkownik niezalogowany.');
        }

        $grade = $gradeRepository->find($id);

        if (!$grade) {
            throw new NotFoundHttpException('Ocena nie została znaleziona.');
        }

        $group = $grade->getGroupp();
        if ($group->getProfessor()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Nie jesteś profesorem tej grupy.');
        }

        $em->remove($grade);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ocena została usunięta.'
        ]);
    }


}
