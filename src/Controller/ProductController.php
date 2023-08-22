<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * List of products.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the list of all products.",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Product::class, groups={"getProducts"}))
     *     )
     * )
     *
     * @OA\Tag(name="Product")
     *
     * @throws InvalidArgumentException
     */
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMER', message: 'You do not have the required rights to view the list of products.')]
    public function getProductsList(TagAwareCacheInterface $cache, Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheId = 'getProductsList-'.$page.'-'.$limit;

        $productsList = $cache->get($cacheId, function (ItemInterface $item) use ($page, $limit) {
            $item->tag('productsCache');

            return $this->productRepository->findAllWithPagination($page, $limit);
        });

        $context = SerializationContext::create()->setGroups('getProducts');
        $jsonProductsList = $this->serializer->serialize($productsList, 'json', $context);

        return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);
    }
}
