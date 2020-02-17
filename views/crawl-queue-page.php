<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>URL in Crawl Queue</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach( $view['urls'] as $url ) : ?>
            <tr>
                <td><?php echo $url; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

