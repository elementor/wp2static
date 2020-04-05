<style>
button.wp2static-button {
    float:left;
    margin-right: 10px !important;
}
</style>

<p><i><a href="<?php echo admin_url('admin.php?page=wp2static-caches'); ?>">Refresh page</a> to see latest status</i><p>

<table class="widefat striped">
    <thead>
        <tr>
            <th>Cache Type</th>
            <th>Statistics</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Crawl Queue (Detected URLs)</td>
            <td><?php echo $view['crawlQueueTotalURLs']; ?> URLs in database</td>
            <td>
                <a href="<?php echo admin_url('admin.php?page=wp2static-crawl-queue'); ?>" target="_blank"><button class="wp2static-button button btn-danger">Show URLs</button></a>
<!-- TODO: allow downloading zipped CSV of all lists  <a href="#"><button class="wp2static-button button btn-danger">Download List</button></a> -->

                <form
                    name="wp2static-crawl-queue-delete"
                    method="POST"
                    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

                <?php wp_nonce_field( $view['nonce_action'] ); ?>
                <input name="action" type="hidden" value="wp2static_crawl_queue_delete" />

                <button class="wp2static-button button btn-danger">Delete Crawl Queue</button>

                </form>
            </td>
        </tr>
        <tr>
            <td>Crawl cache</td>
            <td><?php echo $view['crawlCacheTotalURLs']; ?> URLs in database</td>
            <td>
                <a href="<?php echo admin_url('admin.php?page=wp2static-crawl-cache'); ?>" target="_blank"><button class="wp2static-button button btn-danger">Show URLs</button></a>

                <form
                    name="wp2static-crawl-cache-delete"
                    method="POST"
                    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

                <?php wp_nonce_field( $view['nonce_action'] ); ?>
                <input name="action" type="hidden" value="wp2static_crawl_cache_delete" />

                <button class="wp2static-button button btn-danger">Delete Crawl Cache</button>

                </form>
            </td>
        </tr>
        <tr>
            <td>Generated Static Site</td>
            <td><?php echo $view['exportedSiteFileCount']; ?> files, using <?php echo $view['exportedSiteDiskSpace']; ?>
                <br>

                <a href="file://<?php echo $view['uploads_path']; ?>wp2static-exported-site" />Path</a>

            </td>
            <td>
                <a href="<?php echo admin_url('admin.php?page=wp2static-static-site'); ?>" target="_blank"><button class="wp2static-button button btn-danger">Show Paths</button></a>

                <form
                    name="wp2static-static-site-delete"
                    method="POST"
                    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

                <?php wp_nonce_field( $view['nonce_action'] ); ?>
                <input name="action" type="hidden" value="wp2static_static_site_delete" />

                <button class="wp2static-button button btn-danger">Delete Files</button>

                </form>
            </td>
        </tr>
        <tr>
            <td>Post-processed Static Site</td>
            <td><?php echo $view['processedSiteFileCount']; ?> files, using <?php echo $view['processedSiteDiskSpace']; ?>
                <br>

                <a href="file://<?php echo $view['uploads_path']; ?>wp2static-processed-site" />Path</a>
            </td>
            <td>
                <a href="<?php echo admin_url('admin.php?page=wp2static-post-processed-site'); ?>" target="_blank"><button class="wp2static-button button btn-danger">Show Paths</button></a>

                <form
                    name="wp2static-post-processed-site-delete"
                    method="POST"
                    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

                <?php wp_nonce_field( $view['nonce_action'] ); ?>
                <input name="action" type="hidden" value="wp2static_post_processed_site_delete" />

                <button class="wp2static-button button btn-danger">Delete Files</button>

                </form>
            </td>
        </tr>

        <tr>
            <td>Deploy cache</td>
            <td><?php echo $view['deployCacheTotalPaths']; ?> Paths in database</td>
            <td>
                <a href="<?php echo admin_url('admin.php?page=wp2static-deploy-cache'); ?>" target="_blank"><button class="wp2static-button button btn-danger">Show Paths</button></a>

                <form
                    name="wp2static-deploy-cache-delete"
                    method="POST"
                    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

                <?php wp_nonce_field( $view['nonce_action'] ); ?>
                <input name="action" type="hidden" value="wp2static_deploy_cache_delete" />

                <button class="wp2static-button button btn-danger">Delete Deploy Cache</button>

                </form>
            </td>
        </tr>
    </tbody>
</table>

<br>

<form
    name="wp2static-delete-all-caches"
    method="POST"
    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

<?php wp_nonce_field( $view['nonce_action'] ); ?>
<input name="action" type="hidden" value="wp2static_delete_all_caches" />

<button class="wp2static-button button btn-danger">Delete all caches</button>

</form>
