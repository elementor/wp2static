<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>Paths in Post-processed Static Site</th>
        </tr>
    </thead>
    <tbody>

        <?php if ( ! $view['paths'] ) : ?>
            <tr>
                <td>Post-processed site directory is empty.</td>
            </tr>
        <?php endif; ?>

        <?php foreach( $view['paths'] as $path ) : ?>
            <tr>
                <td><?php echo $path; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

