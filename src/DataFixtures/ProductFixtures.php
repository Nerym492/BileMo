<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function load(ObjectManager $manager)
    {
        $this->addProduct('Samsung Galaxy A54', 'Samsung', 'The best-value smartphone', 429);
        $this->addProduct(
            'Xiaomi Redmi Note 12 5G',
            'Xiaomi',
            'The best smartphone under 300 euros',
            239
        );
        $this->addProduct(
            'Apple iPhone 14 Pro',
            'Apple',
            'The best iPhone available',
            1109
        );
        $this->addProduct(
            'Google Pixel 7',
            'Google',
            'The best smartphone for Android',
            599
        );

        $manager->flush();
    }

    private function addProduct(string $name, string $brand, string $description, float $price)
    {
        $product = new Product();
        $product->setName($name);
        $product->setBrand($brand);
        $product->setDescription($description);
        $product->setPrice($price);

        $this->entityManager->persist($product);
    }
}
