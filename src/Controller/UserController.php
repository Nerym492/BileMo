<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/api/users', name: 'users', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMER', message: 'You do not have the required rights to view the list of users.')]
    public function getUserList(TagAwareCacheInterface $cache, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheId = 'getUserList-'.$page.'-'.$limit;

        $userList = $cache->get($cacheId, function (ItemInterface $item) use ($page, $limit) {
            $item->tag('usersCache');

            return $this->userRepository->findByCustomer(
                $this->security->getUser()->getUserIdentifier(),
                $page,
                $limit
            );
        });

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUsers = $this->serializer->serialize($userList, 'json', $context);

        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    #[Route('api/users', name: 'createUser', methods: ['POST'])]
    #[isGranted('ROLE_CUSTOMER', message: 'You do not have the required rights to create a new user.')]
    public function addUser(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        // Link the logged customer to the created user
        $loggedCustomerMail = $this->security->getUser()->getUserIdentifier();
        $loggedCustomer = $this->customerRepository->findOneBy(['email' => $loggedCustomerMail]);
        $user->setCustomer($loggedCustomer);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $context = SerializationContext::create()->setGroups('getUsers');
        $jsonUser = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMER', message: 'You do not have the required rights to see a detailed user.')]
    public function getDetailUser(User $user, TagAwareCacheInterface $cache): JsonResponse
    {
        $context = SerializationContext::create()->setGroups('getUsers');

        $cacheId = 'detailUser-'.$user->getId();

        $users = $cache->get($cacheId, function (ItemInterface $item) use ($user) {
            $item->tag('usersCache');

            return $this->userRepository->findByCustomer(
                email: $this->security->getUser()->getUserIdentifier(),
                user: $user
            );
        });

        $jsonUsers = $this->serializer->serialize($users, 'json', $context);

        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    #[IsGranted('ROLE_CUSTOMER', message: 'You do not have the required rights to delete a user.')]
    public function deleteUser(User $user, TagAwareCacheInterface $cache): JsonResponse
    {
        $customerMail = $this->security->getUser()->getUserIdentifier();
        $customerId = $this->customerRepository->findOneBy(['email' => $customerMail]);

        if ($user->getCustomer()->getId() !== $customerId) {
            $errorMessage = ['error' => 'Your are not authorized to delete this user.'];
            $jsonErrorMessage = $this->serializer->serialize($errorMessage, 'json');

            return new JsonResponse(
                $jsonErrorMessage,
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        // Empty the cache
        $cache->invalidateTags(['usersCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
