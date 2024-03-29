<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Helper\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends PaginatedRepository
{
    public function __construct(RegistryInterface $registry, Paginator $paginator)
    {
        parent::__construct($registry, Customer::class, $paginator);
    }
}
