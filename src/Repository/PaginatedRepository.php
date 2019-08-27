<?php

namespace App\Repository;


use App\Helper\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class PaginatedRepository extends ServiceEntityRepository
{
    public const KEY_PAGING_ENTITIES = "entities";
    public const KEY_PAGING_PAGES_COUNT = "pages_count";
    public const KEY_PAGING_ITEMS_COUNT = "items_count";
    public const KEY_PAGING_ITEMS_PER_PAGE = "items_per_page";
    public const KEY_PAGING_CURRENT_PAGE = "current_page";
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
     * @param array $requestedProperties
     * @param array $orderBy Could be something like ["price" => "ASC"]
     * @param array $criteria
     * @param bool $exactSearch
     * @return array
     */
    public function getPage(
        int $pageNumber,
        int $itemsPerPage,
        ?array $requestedProperties = null,
        ?array $orderBy = null,
        ?array $criteria = null,
        bool $exactSearch = true
    ) {
        $itemsCount = $this->count([]);
        $this->paginator->update($pageNumber, $itemsPerPage, $itemsCount);
        $queryBuilder = $this->createQueryBuilder("a");

        if ($requestedProperties) {
            $queryBuilder->select("a." . implode(", a.", $requestedProperties));
        }

        if ($criteria) {
            $column = array_key_first($criteria);

            if ($exactSearch) {
                $criteriaValue = $criteria[$column];
                $queryBuilder->where("a.{$column} = :criteria");
            } else {
                $criteriaValue = "%{$criteria[$column]}%";
                $queryBuilder->where("a.{$column} LIKE :criteria");
            }

            $queryBuilder->setParameter('criteria', $criteriaValue);
        }

        if ($orderBy) {
            $orderByColumn = array_key_first($orderBy);
            $queryBuilder->orderBy('a.' . $orderByColumn, $orderBy[$orderByColumn])
                ->setFirstResult($this->paginator->pagingOffset)
                ->setMaxResults($this->paginator->itemsPerPage)
            ;
        }

        $entities = $queryBuilder->getQuery()->execute();

        return [
            self::KEY_PAGING_ENTITIES => $entities,
            self::KEY_PAGING_PAGES_COUNT => $this->paginator->pagesCount,
            self::KEY_PAGING_ITEMS_COUNT => $this->paginator->itemsCount,
            self::KEY_PAGING_ITEMS_PER_PAGE => $this->paginator->itemsPerPage,
            self::KEY_PAGING_CURRENT_PAGE => $this->paginator->currentPage,
            self::KEY_PAGING_NEXT_PAGE => $this->paginator->nextPage,
            self::KEY_PAGING_PREVIOUS_PAGE => $this->paginator->previousPage
        ];
    }
}
