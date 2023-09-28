<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Psr\Cache\InvalidArgumentException;
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

    /**
     * List of users linked to a customer.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the list of users linked to a customer.",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Expired JWT Token"
     * )
     * @OA\Parameter(
     *     name="page",
     *     description="Page number",
     *     in="query",
     *     @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     description="Number of elements per page",
     *     in="query",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Tag(name="User")
     *
     * @throws InvalidArgumentException
     */
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

    /**
     * Create a new user link to a customer.
     *
     * @OA\Response(
     *     response=201,
     *     description="Returns the user who has just been created.",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="The user is not valid."
     * )
     * @OA\Response(
     *     response=401,
     *     description="Expired JWT Token"
     * )
     * @OA\RequestBody(
     *     description="Create a new user.",
     *     @OA\JsonContent(ref=@Model(type=User::class, groups={"userCreation"}))
     * )
     *
     * @OA\Tag(name="User")
     * @throws InvalidArgumentException
     */
    #[Route('api/users', name: 'createUser', methods: ['POST'])]
    #[isGranted('ROLE_CUSTOMER', message: 'You do not have the required rights to create a new user.')]
    public function addUser(Request $request, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
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

        // Empty the cache
        $cache->invalidateTags(['usersCache']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    /**
     * Details of a user linked to a customer.
     * @OA\Response(
     *     response=200,
     *     description="Return the details of a user.",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Expired JWT Token"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Resource not found"
     * )
     * @OA\Tag(name="User")
     *
     * @throws InvalidArgumentException
     */
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

    /**
     * Delete a user.
     *
     * @OA\Response(
     *     response=204,
     *     description="The user has been successfully deleted."
     * )
     * @OA\Response(
     *     response=400,
     *     description="Not authorized to delete this user."
     * )
     * @OA\Response(
     *     response=401,
     *     description="Expired JWT Token"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Resource not found"
     * )
     * @OA\Tag(name="User")
     *
     * @throws InvalidArgumentException
     */
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
