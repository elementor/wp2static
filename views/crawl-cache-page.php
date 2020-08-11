<div class="wrap">
    <br>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>URLs in Crawl Cache</th>
                <th>Page MD5 Hash</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! $view['urls'] ) : ?>
                <tr>
                    <td colspan="2">Crawl cache is empty.</td>
                </tr>
            <?php endif; ?>

            <?php foreach( $view['urls'] as $url=>$page_hash ) : ?>
                <tr>
                    <td><?php echo $url; ?></td>
            <td><?php echo $page_hash; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
