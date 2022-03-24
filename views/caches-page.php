<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded                              
// phpcs:disable Generic.Files.LineLength.TooLong                                  

/**
 * @var mixed[] $view
 */
?>

<style>
select.wp2static-select {
    width: 165px;
}
</style>

<div class="wrap">
    <p><i><a href="<?php echo admin_url( 'admin.php?page=wp2static-caches' ); ?>">Refresh page</a> to see latest status</i><p>

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
    <!-- TODO: allow downloading zipped CSV of all lists  <a href="#"><button class="button btn-danger">Download List</button></a> -->

                    <form
                        name="wp2static-crawl-queue-delete"
                        method="POST"
                        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                        <?php wp_nonce_field( $view['nonce_action'] ); ?>

                        <select name="action" class="wp2static-select">
                            <option value="wp2static_crawl_queue_show">Show URLs</option>
                            <option value="wp2static_crawl_queue_delete">Delete Crawl Queue</option>
                        </select>

                        <button class="button btn-danger">Go</button>

                    </form>
                </td>
            </tr>
            <tr>
                <td>Crawl Cache</td>
                <td><?php echo $view['crawlCacheTotalURLs']; ?> URLs in database</td>
                <td>
                    <form
                        name="wp2static-crawl-cache-delete"
                        method="POST"
                        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                        <?php wp_nonce_field( $view['nonce_action'] ); ?>

                        <select name="action" class="wp2static-select">
                            <option value="wp2static_crawl_cache_show">Show URLs</option>
                            <option value="wp2static_crawl_cache_delete">Delete Crawl Cache</option>
                        </select>

                        <button class="button btn-danger">Go</button>

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
                    <form
                        name="wp2static-static-site-delete"
                        method="POST"
                        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                        <?php wp_nonce_field( $view['nonce_action'] ); ?>

                        <select name="action" class="wp2static-select">
                            <option value="wp2static_static_site_show">Show Paths</option>
                            <option value="wp2static_static_site_delete">Delete Files</option>
                        </select>

                        <button class="button btn-danger">Go</button>

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
                    <form
                        name="wp2static-post-processed-site-delete"
                        method="POST"
                        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                        <?php wp_nonce_field( $view['nonce_action'] ); ?>

                        <select name="action" class="wp2static-select">
                            <option value="wp2static_post_processed_site_show">Show Paths</option>
                            <option value="wp2static_post_processed_site_delete">Delete Files</option>
                        </select>

                        <button class="button btn-danger">Go</button>

                    </form>
                </td>
            </tr>

            <?php
            $deploy_cache_rows
                = isset( $view['deployCacheTotalPaths'] )
                ? count( $view['deployCacheTotalPaths'] )
                : 1;
            ?>
            <tr>
                <td rowspan="<?php echo $deploy_cache_rows; ?>">Deploy Cache</td>
                    <?php $namespaces = array_keys( $view['deployCacheTotalPaths'] ); ?>
                    <?php if ( $namespaces ) { ?>
                        <td><?php echo $view['deployCacheTotalPaths'][ $namespaces[0] ]; ?> Paths in database for <code><?php echo $namespaces[0]; ?></code></td>
                    <?php } else { ?>
                        <td>0 paths in database</td>
                    <?php } ?>
                    <td>
                        <form
                            name="wp2static-post-processed-site-delete"
                            method="POST"
                            action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                            <?php wp_nonce_field( $view['nonce_action'] ); ?>

                            <select name="action" class="wp2static-select">
                                <option value="wp2static_deploy_cache_show">Show Paths</option>
                                <option value="wp2static_deploy_cache_delete">Delete Deploy Cache</option>
                            </select>

                            <input name="deploy_namespace" type="hidden" value="<?php echo $namespaces[0]; ?>" />

                            <button class="button btn-danger">Go</button>

                        </form>
                    </td>
                    <?php for ( $i = 1; $i < $deploy_cache_rows; $i++ ) : ?>
                        </tr>
                        <tr>
                        <td><?php echo $view['deployCacheTotalPaths'][ $namespaces[ $i ] ]; ?> Paths in database for <code><?php echo $namespaces[ $i ]; ?></code></td>
                        <td>
                            <form
                                name="wp2static-deploy-cache-delete"
                                method="POST"
                                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                                <?php wp_nonce_field( $view['nonce_action'] ); ?>

                                <select name="action" class="wp2static-select">
                                    <option value="wp2static_deploy_cache_show">Show Paths</option>
                                    <option value="wp2static_deploy_cache_delete">Delete Deploy Cache</option>
                                </select>

                                <input name="deploy_namespace" type="hidden" value="<?php echo $namespaces[ $i ]; ?>" />

                                <button class="button btn-danger">Go</button>

                            </form>
                        </td>
                    <?php endfor; ?>
            </tr>
            </tbody>
        </table>

        <br>

        <form
            name="wp2static-delete-all-caches"
            method="POST"
            action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

            <?php wp_nonce_field( $view['nonce_action'] ); ?>
            <input name="action" type="hidden" value="wp2static_delete_all_caches" />

            <button class="button btn-danger">Delete all caches</button>

        </form>
    </div>
</div>
