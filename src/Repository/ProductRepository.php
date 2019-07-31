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
    public const KEY_PAGING_ENTITIES = "entities";
    public const KEY_PAGING_COUNT = "pages_count";
    public const KEY_PAGING_NEXT_PAGE = "next_page";
    public const KEY_PAGING_PREVIOUS_PAGE = "previous_page";

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
     * Get an array containing some paging information and an array of products regarding to the number of products required per page, the page number and some optional criteria
     *
     * @param int $pageNumber
     * @param int $itemsPerPage
     * @param array $orderBy
     * @param array $criteria
     * @param bool $exactSearch
     * @return array
     */
    public function getPage(
        int $pageNumber,
        int $itemsPerPage,
        array $orderBy = ["price" => "ASC"],
        array $criteria = null,
        bool $exactSearch = true
    ) {
        $this->paginator->update($pageNumber, $itemsPerPage, $this->count([]));

        if ($exactSearch) {
            $products =  $this->findBy(
                $criteria ?? [],
                $orderBy,
                $this->paginator->itemsPerPage,
                $this->paginator->pagingOffset
            );
        } else {
            $column = array_key_first($criteria);
            $orderByColumn = array_key_first($orderBy);
            $criteriaValue = "%{$criteria[$column]}%";
            $queryBuilder = $this->createQueryBuilder("p")
                ->andWhere('p.' . $column . ' LIKE :criteria')
                ->setParameter('criteria', $criteriaValue)
                ->orderBy('p.' . $orderByColumn, $orderBy[$orderByColumn])
                ->setFirstResult($this->paginator->pagingOffset)
                ->setMaxResults($this->paginator->itemsPerPage)
                ->getQuery();

            $products = $queryBuilder->execute();
        }

        return [
            self::KEY_PAGING_ENTITIES => $products,
            self::KEY_PAGING_COUNT => $this->paginator->pagesCount,
            self::KEY_PAGING_NEXT_PAGE => $this->paginator->nextPage,
            self::KEY_PAGING_PREVIOUS_PAGE => $this->paginator->previousPage
        ];
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
