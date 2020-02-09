
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
                <a href="#"><button class="button btn-danger">Show URLs</button></a>
                <a href="#"><button class="button btn-danger">Download List</button></a>
                <a href="#"><button class="button btn-danger">Clear Crawl Queue</button></a>
            </td>
        </tr>
        <tr>
            <td>Crawl cache</td>
            <td><?php echo $view['crawlCacheTotalURLs']; ?> URLs in database</td>
            <td>
                <a href="#"><button class="button btn-danger">Show URLs</button></a>
                <a href="#"><button class="button btn-danger">Download List</button></a>
                <a href="#"><button class="button btn-danger">Clear Crawl Cache</button></a>
            </td>
        </tr>
        <tr>
            <td>Generated Static Site</td>
            <td><?php echo $view['exportedSiteFileCount']; ?> files, using <?php echo $view['exportedSiteDiskSpace']; ?>
                <br>

                <a href="file://<?php echo $view['uploads_path']; ?>wp2static-exported-site" />Path</a>

            </td>
            <td>
                <a href="#"><button class="button btn-danger">Download List</button></a>
                <a href="#"><button class="button btn-danger">Delete Files</button></a>
            </td>
        </tr>
        <tr>
            <td>Post-processed Static Site</td>
            <td><?php echo $view['processedSiteFileCount']; ?> files, using <?php echo $view['processedSiteDiskSpace']; ?>
                <br>

                <a href="file://<?php echo $view['uploads_path']; ?>wp2static-processed-site" />Path</a>
            </td>
            <td>
                <?php if ( $view['zip_path'] ) : ?>
                    <a href="<?php echo $view['zip_url']; ?>"><button class="button btn-danger">Download ZIP</button></a>
                <?php endif; ?>
                <a href="#"><button class="button btn-danger">Download List</button></a>
                <a href="#"><button class="button btn-danger">Delete Files</button></a>
            </td>
        </tr>
        <tr>
            <td>Deploy cache</td>
            <td><?php echo $view['deployCacheTotalURLs']; ?> URLs in database</td>
            <td>
                <a href="#"><button class="button btn-danger">Show URLs</button></a>
                <a href="#"><button class="button btn-danger">Download List</button></a>
                <a href="#"><button class="button btn-danger">Clear Deploy Cache</button></a>
            </td>
        </tr>
    </tbody>
</table>

