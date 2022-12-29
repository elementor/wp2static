<?php

// phpcs:disable Generic.Files.LineLength.MaxExceeded
// phpcs:disable Generic.Files.LineLength.TooLong

use WP2Static\URLHelper;

?>

<h2 class="screen-reader-text">Crawl Queue list navigation</h2>
<div class="tablenav-pages">
    <span class="displaying-num"><?php echo number_format( $paginator_total_records ); ?> items</span>
    <span class="pagination-links">
        <?php if ( $paginator_page === $paginator_first_page ) : ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
        <?php else : ?>
            <a class="first-page button" href="<?php echo URLHelper::modifyUrl( [ 'paged' => 1 ] ); ?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
            <a class="prev-page button" href="<?php echo URLHelper::modifyUrl( [ 'paged' => $paginator_page - 1 ] ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
        <?php endif; ?>
        <span class="paging-input">
            <label for="current-page-selector" class="screen-reader-text">Current Page</label>
            <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $paginator_page; ?>" size="3" aria-describedby="table-paging">
            <span class="tablenav-paging-text"> of 
                <span class="total-pages"><?php echo $paginator_last_page; ?></span>
            </span>
        </span>
        <?php if ( $paginator_page === $paginator_last_page ) : ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
        <?php else : ?>
            <a class="next-page button" href="<?php echo URLHelper::modifyUrl( [ 'paged' => $paginator_page + 1 ] ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
            <a class="last-page button" href="<?php echo URLHelper::modifyUrl( [ 'paged' => $paginator_last_page ] ); ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
        <?php endif; ?>
    </span>
</div>
