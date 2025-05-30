<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UniversityRepository;
use App\Entity\University;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Subject;
use App\Entity\FieldOfStudy;
use App\Entity\SubjectOfInstance;
use App\Repository\FieldOfStudyRepository;
use App\Repository\SubjectOfInstanceRepository;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ApiUniversityController extends AbstractController
{
    #[Route('/api/university/{id}', name: 'get_university', methods: ['GET'])]
    public function getUniversity(int $id, UniversityRepository $universityRepository): JsonResponse
    {
        $university = $universityRepository->find($id);

        if (!$university) {
            return $this->json(['error' => 'University not found'], 404);
        }

        $data = [
            'id' => $university->getId(),
            'name' => $university->getName(),
            'address' => $university->getAddress(),
            'rector' => $university->getRector() ? [
                'id' => $university->getRector()->getId(),
                'firstname' => $university->getRector()->getFirstName(),
                'lastname' => $university->getRector()->getLastName(),
            ] : null,
            'fieldOfStudy' => [],
        ];

        foreach ($university->getFieldOfStudies() as $field) {
            $data['fieldOfStudies'][] = [
                'id' => $field->getId(),
                'name' => $field->getName(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/university/{id}/field-of-study', name: 'add_field_of_study', methods: ['POST'])]
    public function addFieldOfStudy(int $id,Request $request,UniversityRepository $universityRepository,EntityManagerInterface $em): JsonResponse {
        $university = $universityRepository->find($id);

        if (!$university) {
            return $this->json(['error' => 'University not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($university->getRector() !== $user) {
            return $this->json(['error' => 'Access denied. Only rector can add fields of study.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name']) || empty($data['name'])) {
            return $this->json(['error' => 'Missing field: name'], Response::HTTP_BAD_REQUEST);
        }

        $field = new FieldOfStudy();
        $field->setName($data['name']);
        $field->setUniversity($university);

        $em->persist($field);
        $em->flush();

        return $this->json([
            'message' => 'Field of study added successfully',
            'id' => $field->getId(),
            'name' => $field->getName()
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/field-of-study/{id}/subject', name: 'add_subject', methods: ['POST'])]
    public function addSubject(int $id,Request $request,FieldOfStudyRepository $fieldOfStudyRepository,EntityManagerInterface $em): JsonResponse {
        $fieldOfStudy = $fieldOfStudyRepository->find($id);

        if (!$fieldOfStudy) {
            return $this->json(['error' => 'Field of study not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $university = $fieldOfStudy->getUniversity();

        if ($university->getRector() !== $user) {
            return $this->json(['error' => 'Access denied. Only rector can add subjects.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name']) || empty($data['name'])) {
            return $this->json(['error' => 'Missing field: name'], Response::HTTP_BAD_REQUEST);
        }

        $subject = new Subject();
        $subject->setName($data['name']);
        $subject->setFieldOfStudy($fieldOfStudy);

        $em->persist($subject);
        $em->flush();

        return $this->json([
            'message' => 'Subject added successfully',
            'id' => $subject->getId(),
            'name' => $subject->getName()
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/universities', name: 'get_all_universities', methods: ['GET'])]
    public function getAllUniversities(UniversityRepository $universityRepository): JsonResponse
    {
        $universities = $universityRepository->findAll();

        $result = [];

        foreach ($universities as $university) {
            $fields = [];
            foreach ($university->getFieldOfStudies() as $field) {
                $fields[] = [
                    'id' => $field->getId(),
                    'name' => $field->getName(),
                ];
            }

            $rector = $university->getRector();
            $result[] = [
                'id' => $university->getId(),
                'name' => $university->getName(),
                'address' => $university->getAddress(),
                'rector' => $rector ? [
                    'id' => $rector->getId(),
                    'firstname' => $rector->getFirstName(),
                    'lastname' => $rector->getLastName(),
                ] : null,
                'fieldOfStudy' => $fields,
            ];
        }

        return $this->json($result);
    }

    #[Route('/api/subject-of-instances', name: 'api_subject_of_instances', methods: ['GET'])]
    public function getAll(SubjectOfInstanceRepository $repository): JsonResponse
    {
        $instances = $repository->findAll();

        $data = [];

        foreach ($instances as $instance) {
            $subject = $instance->getSubject();
            $coordinator = $instance->getCoordinator();

            $data[] = [
                'id' => $instance->getId(),
                'subject' => [
                    'id' => $subject?->getId(),
                    'name' => $subject?->getName(),
                ],
                'coordinator' => [
                    'id' => $coordinator?->getId(),
                    'firstName' => $coordinator?->getFirstName(),
                    'lastName' => $coordinator?->getLastName(),
                    'email' => $coordinator?->getEmail(),
                ]
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/my-subject-of-instances', name: 'api_my_subject_of_instances', methods: ['GET'])]
    public function getAllMy(SubjectOfInstanceRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu - użytkownik niezalogowany.');
        }
        $instances = $repository->findAll();

        $data = [];

        foreach ($instances as $instance) {
            $subject = $instance->getSubject();
            $coordinator = $instance->getCoordinator();
            if($coordinator->getId() == $user->getId()){
                $data[] = [
                    'id' => $instance->getId(),
                    'subject' => [
                        'id' => $subject?->getId(),
                        'name' => $subject?->getName(),
                    ],
                    'coordinator' => [
                        'id' => $coordinator?->getId(),
                        'firstName' => $coordinator?->getFirstName(),
                        'lastName' => $coordinator?->getLastName(),
                        'email' => $coordinator?->getEmail(),
                    ]
                ];
            }
        }

        return $this->json($data);
    }

    #[Route('/api/field-of-study/{id}', name: 'get_field_of_study', methods: ['GET'])]
    public function getFieldOfStudy(int $id, EntityManagerInterface $em): JsonResponse
    {
        $fieldOfStudy = $em->getRepository(FieldOfStudy::class)->find($id);

        if (!$fieldOfStudy) {
            return $this->json(['error' => 'Field of study not found'], Response::HTTP_NOT_FOUND);
        }

        $subjects = $fieldOfStudy->getSubjects();

        $subjectData = [];

        foreach ($subjects as $subject) {

            $subjectData[] = [
                'id' => $subject->getId(),
                'name' => $subject->getName(),
            ];
        }

        $responseData = [
            'id' => $fieldOfStudy->getId(),
            'name' => $fieldOfStudy->getName(),
            'subjects' => $subjectData,
        ];

        return $this->json($responseData, Response::HTTP_OK);
    }

    #[Route('/api/subject-details/{id}', name: 'get_subject_details', methods: ['GET'])]
    public function getSubjectDetails(int $id, EntityManagerInterface $em): JsonResponse
    {
        $subject = $em->getRepository(Subject::class)->find($id);

        if (!$subject) {
            return $this->json(['error' => 'Subject not found'], Response::HTTP_NOT_FOUND);
        }

        $subjectOfInstance = $em->getRepository(SubjectOfInstance::class)->findOneBy(['subject' => $subject]);

        $groupsData = [];
        $subjectOfInstanceData = null;

        if ($subjectOfInstance) {
            $coordinator = $subjectOfInstance->getCoordinator();

            $subjectOfInstanceData = [
                'id' => $subjectOfInstance->getId(),
                'coordinator' => $coordinator ? [
                    'id' => $coordinator->getId(),
                    'firstName' => $coordinator->getFirstName(),
                    'lastName' => $coordinator->getLastName(),
                    'email' => $coordinator->getEmail(),
                ] : null,
            ];

            foreach ($subjectOfInstance->getGroupss() as $group) {
                $professor = $group->getProfessor();

                $groupsData[] = [
                    'id' => $group->getId(),
                    'number' => $group->getNumer(),
                    'professor' => $professor ? [
                        'id' => $professor->getId(),
                        'firstName' => $professor->getFirstName(),
                        'lastName' => $professor->getLastName(),
                        'email' => $professor->getEmail(),
                    ] : null,
                ];
            }
        }

        $responseData = [
            'subject' => [
                'id' => $subject->getId(),
                'name' => $subject->getName(),
            ],
            'subjectOfInstance' => $subjectOfInstanceData,
            'groups' => $groupsData,
        ];

        return $this->json($responseData, Response::HTTP_OK);
    }
}
