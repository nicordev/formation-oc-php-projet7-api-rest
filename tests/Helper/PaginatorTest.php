<?php

namespace App\Tests\Service;


use App\Helper\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testFitCurrentPageInBoundaries_positiveOutbound()
    {
        $paginator = new Paginator();
        $paginator->pagesCount = 20;
        $paginator->currentPage = 21;
        $paginator->fitCurrentPageInBoundaries();
        $this->assertEquals(20, $paginator->currentPage);
    }

    public function testFitCurrentPageInBoundaries_negativeOutbound()
    {
        $paginator = new Paginator();
        $paginator->currentPage = -1;
        $paginator->fitCurrentPageInBoundaries();
        $this->assertEquals(1, $paginator->currentPage);
    }

    public function testUpdate_currentPagePositiveOutbound()
    {
        $paginator = new Paginator();
        $itemsPerPage = 10;
        $itemsCount = 100;
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

    public function testUpdate_currentPageNegativeOutbound()
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
    }

    public function testUpdate_itemsPerPageSuperiorThanItemsCount()
    {
        $paginator = new Paginator();
        $itemsPerPage = 10000;
        $itemsCount = 100;
        $paginator->update(
            1,
            $itemsPerPage,
            $itemsCount,
            true
        );
        $this->assertEquals(1, $paginator->currentPage);
        $this->assertEquals(1, $paginator->nextPage);
        $this->assertEquals(1, $paginator->previousPage);
        $this->assertEquals(100, $paginator->itemsPerPage);
    }

    public function testUpdate_negativeItemsPerPage()
    {
        $paginator = new Paginator();
        $itemsPerPage = -1000;
        $itemsCount = 100;
        $paginator->update(
            1,
            $itemsPerPage,
            $itemsCount,
            true
        );
        $this->assertEquals(1, $paginator->currentPage);
        $this->assertEquals(2, $paginator->nextPage);
        $this->assertEquals(1, $paginator->previousPage);
        $this->assertEquals(1, $paginator->itemsPerPage);
    }

    public function testCalculateNextPageNumber_inbound()
    {
        $pagesCount = 20;
        $currentPage = 15;
        $nextPage = Paginator::calculateNextPageNumber($currentPage, $pagesCount);
        $this->assertEquals($currentPage + 1, $nextPage);
    }

    public function testCalculateNextPageNumber_positiveOutbound()
    {
        $pagesCount = 20;
        $currentPage = 4000;
        $nextPage = Paginator::calculateNextPageNumber($currentPage, $pagesCount);
        $this->assertEquals($pagesCount, $nextPage);
    }

    public function testCalculateNextPageNumber_negativeOutbound()
    {
        $pagesCount = 20;
        $currentPage = -4000;
        $nextPage = Paginator::calculateNextPageNumber($currentPage, $pagesCount);
        $this->assertEquals(1, $nextPage);
    }

    public function testCalculatePreviousPageNumber_inbound()
    {
        $pagesCount = 20;
        $currentPage = 15;
        $previousPage = Paginator::calculatePreviousPageNumber($currentPage, $pagesCount);
        $this->assertEquals($currentPage - 1, $previousPage);
    }

    public function testCalculatePreviousPageNumber_positiveOutbound()
    {
        $pagesCount = 20;
        $currentPage = 4000;
        $previousPage = Paginator::calculatePreviousPageNumber($currentPage, $pagesCount);
        $this->assertEquals($pagesCount, $previousPage);
    }

    public function testCalculatePreviousPageNumber_negativeOutbound()
    {
        $pagesCount = 20;
        $currentPage = -4000;
        $previousPage = Paginator::calculatePreviousPageNumber($currentPage, $pagesCount);
        $this->assertEquals(1, $previousPage);
    }
}