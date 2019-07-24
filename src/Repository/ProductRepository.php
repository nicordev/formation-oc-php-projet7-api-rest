<?php

namespace App\Repository;

use App\Entity\Product;
use App\Helper\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * @var Paginator
     */
    private $paginator;

    public function __construct(RegistryInterface $registry, Paginator $paginator)
    {
        parent::__construct($registry, Product::class);

        $this->paginator = $paginator;
    }

    /**
     * Get a page of products regarding to the number of products required per page and the page number
     *
     * @param int $pageNumber
     * @param int $itemsPerPage
     * @param string $criteria
     * @param string $order
     * @return Product[]
     */
    public function getPage(
        int $pageNumber,
        int $itemsPerPage,
        string $criteria = "price",
        string $order = "asc"
    )
    {
        $order = strtoupper($order);
        $this->paginator->update($pageNumber, $itemsPerPage, $this->count([]));

        return $this->findBy(
            [],
            [$criteria => $order],
            $this->paginator->itemsPerPage,
            $this->paginator->pagingOffset
        );
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
