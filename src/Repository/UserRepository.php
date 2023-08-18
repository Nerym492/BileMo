<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findByCustomer(string $email, int $page = 0, int $limit = 0, User $user = null): array
    {
        $usersQuery = $this->createQueryBuilder('u')
            ->leftJoin('u.customer', 'c')
            ->andWhere('c.email = :customerEmail')
            ->setParameter('customerEmail', $email);

        // Filter by user
        if ($user) {
            $usersQuery->andWhere('u.id = :userId')
                ->setParameter('userId', $user->getId());
        }

        if (0 !== $page and 0 !== $limit) {
            $usersQuery->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);
        }

        return $usersQuery->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
