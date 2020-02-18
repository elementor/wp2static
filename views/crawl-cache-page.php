<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>URLs in Crawl Cache</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! $view['urls'] ) : ?>
            <tr>
                <td>Crawl cache is empty.</td>
            </tr>
        <?php endif; ?>

        <?php foreach( $view['urls'] as $url ) : ?>
            <tr>
                <td><?php echo $url; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

