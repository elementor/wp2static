<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded                              
// phpcs:disable Generic.Files.LineLength.TooLong                                  

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
?>

<div class="wrap">
    <br>

    <form id="posts-filter" method="GET">
        <input type="hidden" name="page" value="<?php echo $paginator_index; ?>" />
        <input type="hidden" name="paged" value="<?php echo $paginator_page; ?>" />

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
        
            <?php
            // outputs paginator template
            /** @phpstan-ignore-next-line */
             $view['paginatorRender'];
            ?>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th>Paths to Deploy Cache</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! $view['paginatorTotalRecords'] ) : ?>
                    <tr>
                        <th>&nbsp;</th>
                        <td>Deploy Cache is empty.</td>
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
