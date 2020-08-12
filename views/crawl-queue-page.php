<br>

<form id="posts-filter" method="GET">
    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />

    <p class="search-box">
        <label class="screen-reader-text" for="post-search-input">Search Crawl Queue URLs:</label>
        <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( @$_GET['s'] ); ?>">
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
	
        <h2 class="screen-reader-text">Crawl Queue list navigation</h2>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo number_format($view['total_count']); ?> items</span>
            <span class="pagination-links">
                <?php if ($view['page'] === 1): ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                <?php else: ?>
                    <a class="first-page button" href="<?php echo \WP2Static\URLHelper::modifyUrl(['paged' => 1]); ?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
                    <a class="prev-page button" href="<?php echo \WP2Static\URLHelper::modifyUrl(['paged' => $view['page'] - 1]); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
                <?php endif; ?>
                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                    <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $view['page']; ?>" size="3" aria-describedby="table-paging">
                    <span class="tablenav-paging-text"> of 
                        <span class="total-pages"><?php echo $view['pages']; ?></span>
                    </span>
                </span>
                <?php if ($view['page'] === $view['pages']): ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                <?php else: ?>
                    <a class="next-page button" href="<?php echo \WP2Static\URLHelper::modifyUrl(['paged' => $view['page'] + 1]); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
                    <a class="last-page button" href="<?php echo \WP2Static\URLHelper::modifyUrl(['paged' => $view['pages']]); ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
                <?php endif; ?>
            </span>
        </div>
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
            <?php if ( ! $view['urls'] ) : ?>
                <tr>
                    <th>&nbsp;</th>
                    <td>Crawl queue is empty.</td>
                </tr>
            <?php endif; ?>

            <?php foreach( $view['urls'] as $id => $url ) : ?>
                <tr>
                    <th scope="row" class="check-column">
                        <label class="screen-reader-text" for="cb-select-<?php echo $id; ?>">
                            Select <?php echo $url; ?>
                        </label>
                        <input id="cb-select-<?php echo $id; ?>" type="checkbox" name="id[]" value="<?php echo $id; ?>">
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
