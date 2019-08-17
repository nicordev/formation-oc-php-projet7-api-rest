<?php

namespace App\Repository;

use App\Entity\User;
use App\Helper\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends PaginatedRepository
{
    public function __construct(RegistryInterface $registry, Paginator $paginator)
    {
        parent::__construct($registry, User::class, $paginator);
    }
}
