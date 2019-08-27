<?php

namespace App\Helper;

class Paginator
{
    public $currentPage;
    public $nextPage;
    public $previousPage;
    public $itemsPerPage;
    public $itemsCount;
    public $pagesCount;
    public $pagingOffset;

    /**
     * Paginator constructor.
     *
     * @param int|null $currentPage
     * @param int|null $itemsPerPage
     * @param int|null $itemsCount
     * @param bool $fitCurrentPageInBoundaries
     */
    public function __construct(
        ?int $currentPage = null,
        ?int $itemsPerPage = null,
        ?int $itemsCount = null,
        bool $fitCurrentPageInBoundaries = true
    ) {
        $this->update(
            $currentPage,
            $itemsPerPage,
            $itemsCount,
            $fitCurrentPageInBoundaries
        );
    }

    /**
     * Update the attributes all at once
     *
     * @param int|null $currentPage
     * @param int|null $itemsPerPage
     * @param int|null $itemsCount
     * @param bool $fitCurrentPageInBoundaries
     */
    public function update(
        ?int $currentPage = null,
        ?int $itemsPerPage = null,
        ?int $itemsCount = null,
        bool $fitCurrentPageInBoundaries = true
    ) {
        if ($currentPage) {
            $this->currentPage = $currentPage;
        }
        if ($itemsPerPage) {
            if ($itemsPerPage < 1) {
                $itemsPerPage = 1;
            }
            $this->itemsPerPage = $itemsPerPage;
        }
        if ($itemsCount) {
            $this->itemsCount = $itemsCount;
        }
        if ($itemsCount && $itemsPerPage) {
            if ($itemsPerPage > $itemsCount) {
                $this->itemsPerPage = $itemsCount;
            }
            $this->pagesCount = self::countPages($this->itemsCount, $this->itemsPerPage);
            if ($fitCurrentPageInBoundaries) {
                $this->fitCurrentPageInBoundaries();
            }
        }
        if ($currentPage && $itemsPerPage) {
            $this->pagingOffset = self::calculatePagingOffset($this->currentPage, $itemsPerPage); // Use of the object's attribute to stay in boundaries
        }
        if ($currentPage && $this->pagesCount) {
            $this->nextPage = self::calculateNextPageNumber($this->currentPage, $this->pagesCount);
            $this->previousPage = self::calculatePreviousPageNumber($this->currentPage, $this->pagesCount);
        }
    }

    /**
     * Set the current page within paging count
     */
    public function fitCurrentPageInBoundaries()
    {
        $this->currentPage = self::applyBoundaries($this->currentPage, 1, $this->pagesCount ?? $this->currentPage);
    }

    // Static

    /**
     * Give the correct page number within the boundaries
     *
     * @param int $page
     * @param int $max
     * @param int $min
     * @return int
     */
    public static function applyBoundaries(int $page, int $min, int $max)
    {
        if ($page < $min) {
            return $min;
        } elseif ($page > $max) {
            return $max;
        }
        return $page;
    }

    /**
     * Calculate the next page number
     *
     * @param int $currentPage
     * @param int $pagesCount
     * @return int
     */
    public static function calculateNextPageNumber(int $currentPage, int $pagesCount): int
    {
        return self::applyBoundaries($currentPage + 1, 1, $pagesCount);
    }

    /**
     * Calculate the previous page number
     *
     * @param int $currentPage
     * @param int $pagesCount
     * @return int
     */
    public static function calculatePreviousPageNumber(int $currentPage, int $pagesCount): int
    {
        return self::applyBoundaries($currentPage - 1, 1, $pagesCount);
    }

    /**
     * Calculate the offset for paging
     *
     * @param int $page
     * @param int $linesPerPage
     * @return float|int
     */
    public static function calculatePagingOffset(int $page, int $linesPerPage): int
    {
        return ($page - 1) * $linesPerPage;
    }

    /**
     * Count pages
     *
     * @param int $itemsCount
     * @param int $itemsPerPage
     * @return int
     */
    public static function countPages(int $itemsCount, int $itemsPerPage): int
    {
        return ceil($itemsCount / $itemsPerPage);
    }
}
