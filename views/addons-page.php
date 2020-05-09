<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>Enabled</th>
            <th>Name</th>
            <th>Type</th>
            <th>Documentation URL</th>
            <th>Configure</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( ! $view['addons'] ) : ?>
            <tr>
                <td colspan="4">No addons are installed. <a href="https://wp2static.com/download">Get Add-Ons</a></td>
            </tr>
        <?php endif; ?>


        <?php foreach( $view['addons'] as $addon ) : ?>
            <tr>
                <td>
                    <?php if ( $addon->enabled ) : ?>
                        <a href="#"><button>Enabled</button></a?>
                    <?php else: ?>
                        <a href="#"><button>Disabled</button></a?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $addon->name; ?>
                    <br>
                    <?php echo $addon->description; ?>
                </td>
                <td><?php echo $addon->type; ?></td>
                <td>
                    <a href="<?php echo $addon->documentation_url; ?>"><span class="dashicons dashicons-book-alt"></span></a>
                </td>
                <td>
                    <a href="<?php echo esc_url( admin_url("admin.php?page={$addon->slug}") ); ?>"><span class="dashicons dashicons-admin-generic"></span></a>
                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<br> 

