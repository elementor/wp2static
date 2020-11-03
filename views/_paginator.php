<?php

use WP2Static\URLHelper;

?>

<h2 class="screen-reader-text">Crawl Queue list navigation</h2>
<div class="tablenav-pages">
    <span class="displaying-num"><?php echo number_format($this->totalRecords()); ?> items</span>
    <span class="pagination-links">
        <?php if ($this->page() === $this->firstPage()): ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
        <?php else: ?>
            <a class="first-page button" href="<?php echo URLHelper::modifyUrl(['paged' => 1]); ?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
            <a class="prev-page button" href="<?php echo URLHelper::modifyUrl(['paged' => $this->page() - 1]); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
        <?php endif; ?>
        <span class="paging-input">
            <label for="current-page-selector" class="screen-reader-text">Current Page</label>
            <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $this->page(); ?>" size="3" aria-describedby="table-paging">
            <span class="tablenav-paging-text"> of 
                <span class="total-pages"><?php echo $this->lastPage(); ?></span>
            </span>
        </span>
        <?php if ($this->page() === $this->lastPage()): ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
        <?php else: ?>
            <a class="next-page button" href="<?php echo URLHelper::modifyUrl(['paged' => $this->page() + 1]); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
            <a class="last-page button" href="<?php echo URLHelper::modifyUrl(['paged' => $this->lastPage()]); ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
        <?php endif; ?>
    </span>
</div>
