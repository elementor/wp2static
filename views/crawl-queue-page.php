<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>URLs in Crawl Queue</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! $view['urls'] ) : ?>
            <tr>
                <td>Crawl queue is empty.</td>
            </tr>
        <?php endif; ?>

        <?php foreach( $view['urls'] as $url ) : ?>
            <tr>
                <td><?php echo $url; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

