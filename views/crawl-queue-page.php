<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded                              
// phpcs:disable Generic.Files.LineLength.TooLong                                  

/**
 * @var mixed[] $view
 */
?>

<div class="wrap">
    <br>

    <form id="posts-filter" method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
        <input type="hidden" name="paged" value="<?php echo $view['paginator']->page(); ?>" />

        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Search Crawl Queue URLs:</label>
            <input
                type="search"
                id="post-search-input"
                name="s"
                value="<?php echo $_GET['s'] ?? ''; ?>"
            >
            <input type="submit" id="search-submit" class="button" value="Search URLs">
        </p>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1">Bulk Actions</option>
                    <option value="remove">Remove</option>
                </select>
                <input type="submit" id="doaction" class="button action" value="Apply">
            </div>
        
            <?php $view['paginator']->render(); ?>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th>URLs in Crawl Queue</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! $view['paginator']->totalRecords() ) : ?>
                    <tr>
                        <th>&nbsp;</th>
                        <td>Crawl queue is empty.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ( $view['paginator']->records() as $paginator_id => $url ) : ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-<?php echo $paginator_id; ?>">
                                Select <?php echo $url; ?>
                            </label>
                            <input id="cb-select-<?php echo $paginator_id; ?>" type="checkbox" name="id[]" value="<?php echo $paginator_id; ?>">
                            <div class="locked-indicator">
                                <span class="locked-indicator-icon" aria-hidden="true"></span>
                                <span class="screen-reader-text"><?php echo $url; ?></span>
                            </div>
                        </th>
                        <td><?php echo $url; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
