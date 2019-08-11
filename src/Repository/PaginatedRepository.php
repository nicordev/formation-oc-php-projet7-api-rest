<?php

namespace App\Repository;


use App\Helper\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class PaginatedRepository extends ServiceEntityRepository
{
    public const KEY_PAGING_ENTITIES = "entities";
    public const KEY_PAGING_COUNT = "pages_count";
    public const KEY_PAGING_NEXT_PAGE = "next_page";
    public const KEY_PAGING_PREVIOUS_PAGE = "previous_page";

    /**
     * @var Paginator
     */
    private $paginator;

    public function __construct(RegistryInterface $registry, string $entityClass, Paginator $paginator)
    {
        parent::__construct($registry, $entityClass);

        $this->paginator = $paginator;
    }

    /**
     * Get an array containing some paging information and an array of entities regarding to the number of entities required per page, the page number and some optional criteria
     *
     * @param int $pageNumber
     * @param int $itemsPerPage
     * @param array $orderBy Could be something like ["price" => "ASC"]
     * @param array $criteria
     * @param bool $exactSearch
     * @return array
     */
    public function getPage(
        int $pageNumber,
        int $itemsPerPage,
        ?array $orderBy = null,
        ?array $criteria = null,
        bool $exactSearch = true
    ) {
        $this->paginator->update($pageNumber, $itemsPerPage, $this->count([]));

        if ($exactSearch) {
            $entities =  $this->findBy(
                $criteria ?? [],
                $orderBy ?? null,
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

            $entities = $queryBuilder->execute();
        }

        return [
            self::KEY_PAGING_ENTITIES => $entities,
            self::KEY_PAGING_COUNT => $this->paginator->pagesCount,
            self::KEY_PAGING_NEXT_PAGE => $this->paginator->nextPage,
            self::KEY_PAGING_PREVIOUS_PAGE => $this->paginator->previousPage
        ];
    }
}