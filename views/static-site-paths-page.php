<div class="wrap">
    <br>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Paths in Generated Static Site</th>
            </tr>
        </thead>
        <tbody>

            <?php if ( ! $view['paths'] ) : ?>
                <tr>
                    <td>Generated static site directory is empty.</td>
                </tr>
            <?php endif; ?>

            <?php foreach( $view['paths'] as $path ) : ?>
                <tr>
                    <td><?php echo $path; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
