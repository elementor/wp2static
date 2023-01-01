<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded                              
// phpcs:disable Generic.Files.LineLength.TooLong                                  

use WP2Static\URLHelper;

/**
 * @var mixed[] $view
 */

/**
 * @var int $paginator_index
 */
$paginator_index = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT );

/**
 * @var int $paginator_page
 */
$paginator_page = $view['paginatorPage'];

/**
 * @var string $search_term
 */
$search_term = filter_input( INPUT_GET, 's', FILTER_SANITIZE_URL ) ?? '';

/**
 * @var int $paginator_total_records
 */
$paginator_total_records = $view['paginatorTotalRecords'];

/**
 * @var int $paginator_first_page
 */
$paginator_first_page = $view['paginatorFirstPage'];

/**
 * @var int $paginator_last_page
 */
$paginator_last__page = $view['paginatorLastPage'];

?>

<div class="wrap">
    <br>

    <form id="posts-filter" method="GET">
        <input type="hidden" name="page" value="<?php echo strval( $paginator_index ); ?>" />
        <input type="hidden" name="paged" value="<?php echo strval( $paginator_page ); ?>" />

        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Search Crawl Queue URLs:</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>">
            <input type="submit" id="search-submit" class="button" value="Search URLs">
        </p>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1">Bulk Actions</option>
                </select>
                <input type="submit" id="doaction" class="button action" value="Apply">
            </div>
        
            <!-- start Paginator template partial -->
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
            <!-- end Paginator template partial -->
            <br class="clear">
        </div>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th>Paths in Generated Static Site</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! $view['paginatorTotalRecords'] ) : ?>
                    <tr>
                        <th>&nbsp;</th>
                        <td>Generated static site directory is empty.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ( $view['paginatorRecords'] as $paginator_id => $paginator_path ) : ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-<?php echo $paginator_id; ?>">
                                Select <?php echo $paginator_path; ?>
                            </label>
                            <input id="cb-select-<?php echo $paginator_id; ?>" type="checkbox" name="id[]" value="<?php echo $paginator_id; ?>">
                            <div class="locked-indicator">
                                <span class="locked-indicator-icon" aria-hidden="true"></span>
                                <span class="screen-reader-text"><?php echo $paginator_path; ?></span>
                            </div>
                        </th>
                        <td><?php echo $paginator_path; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
