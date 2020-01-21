<h2>WP2Static > Caches</h2>

<h3>Cache Management</h3>


<hr>

<table style="width:100%;text-align:center;">
    <thead>
        <tr>
            <th>Type</th>
            <th>Statistics <i>(refresh page to update)</i></th> 
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Crawl cache</td>
            <td><?php echo $view['crawlCacheTotalURLs']; ?> URLs in database</td>
            <td>
                <a href="#"><button class="button btn-danger">Show URLs</button></a>
                <a href="#"><button class="button btn-danger">Delete Crawl Cache</button></a>
            </td>
        </tr>
        <tr>
            <td>Generated Static Site</td>
            <td><?php echo $view['exportedSiteFileCount']; ?> files, using <?php echo $view['exportedSiteDiskSpace']; ?><br>in path /var/www/html/wp-content/uploads/wp2static-exported-site</td>
            <td>
                <a href="#"><button class="button btn-danger">Download ZIP</button></a>
                <a href="#"><button class="button btn-danger">Delete Files</button></a>
            </td>
        </tr>
        <tr>
            <td>Post-processed Static Site</td>
            <td><?php echo $view['processedSiteFileCount']; ?> files, using <?php echo $view['processedSiteDiskSpace']; ?><br> in path /var/www/html/wp-content/uploads/wp2static-processed-site</td>
            <td>
                <a href="#"><button class="button btn-danger">Download ZIP</button></a>
                <a href="#"><button class="button btn-danger">Delete Files</button></a>
            </td>
        </tr>
        <tr>
            <td>Deploy cache</td>
            <td><?php echo $view['deployCacheTotalURLs']; ?> URLs in database</td>
            <td>
                <a href="#"><button class="button btn-danger">Show URLs</button></a>
                <a href="#"><button class="button btn-danger">Delete Deploy Cache</button></a>
            </td>
        </tr>
    </tbody>
</table>

<hr>

<h3>Cache Options</h3>

<h4>Crawl Caching</h4>

<input name='dontUseCrawlCaching' id='dontUseCrawlCaching' type='checkbox' />
<label for='dontUseCrawlCaching'>Disregard cache and crawl everything</label>

<hr>
<p>The following actions will trigger deletion of URLs from the Crawl Cache:<p>

<table style="width:100%;">
    <thead>
        <tr>
            <th>Entity</th>
            <th>Actions</th>
            <th>Cache Deletion</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Post/Page/Custom Post</td>
            <td>Adding/updating/deleting</td>
            <td>that post/page and related taxonomy URLs</td>
        </tr>
        <tr>
            <td>Theme</td>
            <td>Switching active</td>
            <td>deletes all Crawl Cache</td>
        </tr>
        <tr>
            <td>Plugin</td>
            <td>activation/deactivation</td>
            <td>deletes all Crawl Cache</td>
        </tr>
    </tbody>
</table>

<hr>

<h4>Deploy Caching</h4>

<input name='dontUseDeployCaching' id='dontUseDeployCaching' type='checkbox' />
<label for='dontUseDeployCaching'>Disregard cache and deploy everything</label>

<p>When deploying, WP2Static will check each file to see if it's changed since the last deployment. It will skip unchanged files based on this information. If you want to force an uncached deployment, click this button and any caches will be emptied, requiring a full deploy on the next run.</p>
<hr>

