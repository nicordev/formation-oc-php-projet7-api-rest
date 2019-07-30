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
            $this->itemsPerPage = $itemsPerPage;
        }
        if ($itemsCount) {
            $this->itemsCount = $itemsCount;
        }
        if ($itemsCount && $itemsPerPage) {
            $this->pagesCount = self::countPages($itemsCount, $itemsPerPage);
            if ($fitCurrentPageInBoundaries) {
                $this->fitCurrentPageInBoundaries();
            }
        }
        if ($currentPage && $itemsPerPage) {
            $this->pagingOffset = self::calculatePagingOffset($this->currentPage, $itemsPerPage); // Use of the object's attribute to stay in boundaries
        }
        if ($currentPage && $this->pagesCount) {
            $this->nextPage = self::calculateNextPageNumber($currentPage, $this->pagesCount);
            $this->previousPage = self::calculatePreviousPageNumber($currentPage, $this->pagesCount);
        }
    }

    /**
     * Set the current page within paging count
     */
    public function fitCurrentPageInBoundaries()
    {
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        } elseif (!empty($this->pagesCount) && $this->currentPage > $this->pagesCount) {
            $this->currentPage = $this->pagesCount;
        }
    }

    // Static

    /**
     * Calculate the next page number
     *
     * @param int $currentPage
     * @param int $pagesCount
     * @return int
     */
    public static function calculateNextPageNumber(int $currentPage, int $pagesCount): int
    {
        $nextPage = $currentPage + 1;

        if ($nextPage > $pagesCount) {
            return $currentPage;
        }

        return $nextPage;
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
        $previousPage = $currentPage - 1;

        if ($previousPage < 1) {
            return $currentPage;
        }

        return $previousPage;
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
