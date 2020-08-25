<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase {


    public function testLastPage() {
        $records = array_fill( 0, 9, 0 );
        $expected = 2;
        $actual = ( new Paginator( $records, 5, 1 ) )->lastPage();
        $this->assertEquals(
            $expected,
            $actual
        );

        $records = array_fill( 0, 11, 0 );
        $expected = 3;
        $actual = ( new Paginator( $records, 5, 1 ) )->lastPage();
        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function testDifferentPageSizes() {
        // 9 records, page size of 10
        $records = array_fill( 0, 9, 0 );
        $expected = 1;
        $actual = ( new Paginator( $records, 10, 1 ) )->lastPage();
        $this->assertEquals(
            $expected,
            $actual
        );

        // 11 records, page size of 10
        $records = array_fill( 0, 11, 0 );
        $expected = 2;
        $actual = ( new Paginator( $records, 10, 1 ) )->lastPage();
        $this->assertEquals(
            $expected,
            $actual
        );

        // 11 records, page size of 5
        $records = array_fill( 0, 11, 0 );
        $expected = 3;
        $actual = ( new Paginator( $records, 5, 1 ) )->lastPage();
        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function testPageSize() {
        // 9 records, page size of 10, page 1
        $records = array_fill( 0, 9, 0 );
        $expected = 9;
        $actual = ( new Paginator( $records, 10, 1 ) )->pageSize();
        $this->assertEquals(
            $expected,
            $actual
        );

        // 11 records, page size of 10, page 1
        $records = array_fill( 0, 11, 0 );
        $expected = 10;
        $actual = ( new Paginator( $records, 10, 1 ) )->pageSize();
        $this->assertEquals(
            $expected,
            $actual
        );

        // 11 records, page size of 10, page 3
        $records = array_fill( 0, 23, 0 );
        $expected = 3;
        $actual = ( new Paginator( $records, 10, 3 ) )->pageSize();
        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function testRecords() {
        $records = [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ];
        $expected = [ 1, 2, 3 ];
        $actual = ( new Paginator( $records, 3, 1 ) )->records();
        $this->assertEquals(
            $expected,
            $actual
        );

        // Integer keys are preserved
        $expected = [
            3 => 4,
            4 => 5,
            5 => 6,
        ];
        $actual = ( new Paginator( $records, 3, 2 ) )->records();
        $this->assertEquals(
            $expected,
            $actual
        );

        $records = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
        ];
        $expected = [
            'four' => 4,
            'five' => 5,
            'six' => 6,
        ];
        $actual = ( new Paginator( $records, 3, 2 ) )->records();
        $this->assertEquals(
            $expected,
            $actual
        );
    }
}
