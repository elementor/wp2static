<?php

namespace WP2Static;

class Paginator {
    /**
     * @var array<mixed>
     */
    public $records = [];

    /**
     * @var int
     */
    public $page_size = 100;

    /**
     * @var int
     */
    public $page = 1;

    /**
     * @var int
     */
    public $pages = 1;

    /**
     * @var int
     */
    public $total_records = 0;

    /**
     * Create a new Paginator
     *
     * @param array<mixed> $records
     * @param integer $page_size
     * @param integer $page
     */
    public function __construct( array $records, int $page_size, int $page ) {
        $this->page = $page;
        $this->page_size = $page_size;
        $this->pages = intval( ceil( count( $records ) / $page_size ) );
        $this->total_records = count( $records );
        $this->records = $this->paginateRecords( $records, $page_size, $page );
    }

    public function page() : int {
        return $this->page;
    }

    public function nextPage() : int {
        return min( $this->page + 1, $this->pages );
    }

    public function prevPage() : int {
        return max( 1, $this->page - 1 );
    }

    public function firstPage() : int {
        return 1;
    }

    public function lastPage() : int {
        return $this->pages;
    }

    public function pageSize() : int {
        return count( $this->records );
    }

    public function totalRecords() : int {
        return $this->total_records;
    }

    /**
     * Returns the paginated set of records.
     *
     * @return array<mixed>
     */
    public function records() : array {
        return $this->records;
    }

    public function render() : void {
        require __DIR__ . '/../views/_paginator.php';
    }

    /**
     * Extract only the records for our current page.q
     * Because array_slice doesn't preserve integer keys, we need to split
     * the array into keys and values, array_slice each then recombine
     *
     * @param array<mixed> $records
     * @param int $page_size
     * @param int $page
     * @return array<mixed>
     */
    protected function paginateRecords( array $records, int $page_size, int $page ) : array {
        $keys = array_keys( $records );
        $values = array_values( $records );

        return (array) array_combine(
            array_slice( $keys, ( $page - 1 ) * $page_size, $page_size ),
            array_slice( $values, ( $page - 1 ) * $page_size, $page_size )
        );
    }
}

