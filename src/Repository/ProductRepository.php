<?php

namespace App\Repository;

use App\Entity\Product;
use App\Helper\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends PaginatedRepository
{
    public function __construct(RegistryInterface $registry, Paginator $paginator)
    {
        parent::__construct($registry, Product::class, $paginator);
    }
}
