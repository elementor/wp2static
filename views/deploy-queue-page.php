<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>Path in Deploy Queue</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! $view['paths'] ) : ?>
            <tr>
                <td>Deploy Queue is empty.</td>
            </tr>
        <?php endif; ?>

        <?php foreach( $view['paths'] as $path ) : ?>
            <tr>
                <td><?php echo $path; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

