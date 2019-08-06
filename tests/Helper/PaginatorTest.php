<?php

namespace App\Tests\Service;


use App\Helper\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testFitCurrentPageInBoundaries()
    {
        $paginator = new Paginator();

        $paginator->pagesCount = 20;
        $paginator->currentPage = 21;

        $paginator->fitCurrentPageInBoundaries();
        $this->assertEquals(20, $paginator->currentPage);

        $paginator->currentPage = -1;

        $paginator->fitCurrentPageInBoundaries();
        $this->assertEquals(1, $paginator->currentPage);
    }

    public function testUpdate()
    {
        $paginator = new Paginator();

        $itemsPerPage = 10;
        $itemsCount = 100;

        $paginator->update(
            -5,
            $itemsPerPage,
            $itemsCount,
            true
        );

        $this->assertEquals(1, $paginator->currentPage);
        $this->assertEquals(2, $paginator->nextPage);
        $this->assertEquals(1, $paginator->previousPage);

        $paginator->update(
            1000,
            $itemsPerPage,
            $itemsCount,
            true
        );

        $this->assertEquals(10, $paginator->currentPage);
        $this->assertEquals(10, $paginator->nextPage);
        $this->assertEquals(9, $paginator->previousPage);
    }

    public function testCalculateNextPageNumber()
    {
        $pagesCount = 20;
        $currentPage = 15;
        $nextPage = Paginator::calculateNextPageNumber($currentPage, $pagesCount);
        $this->assertEquals($currentPage + 1, $nextPage);

        $currentPage = 4000;
        $nextPage = Paginator::calculateNextPageNumber($currentPage, $pagesCount);
        $this->assertEquals($pagesCount, $nextPage);

        $currentPage = -4000;
        $nextPage = Paginator::calculateNextPageNumber($currentPage, $pagesCount);
        $this->assertEquals(1, $nextPage);
    }

    public function testCalculatePreviousPageNumber()
    {
        $pagesCount = 20;
        $currentPage = 15;
        $previousPage = Paginator::calculatePreviousPageNumber($currentPage, $pagesCount);
        $this->assertEquals($currentPage - 1, $previousPage);

        $currentPage = 4000;
        $previousPage = Paginator::calculatePreviousPageNumber($currentPage, $pagesCount);
        $this->assertEquals($pagesCount, $previousPage);

        $currentPage = -4000;
        $previousPage = Paginator::calculatePreviousPageNumber($currentPage, $pagesCount);
        $this->assertEquals(1, $previousPage);
    }
}