<div class="wrap">
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
                        <form
                            name="wp2static-crawl-queue-delete"
                            method="POST"
                            action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

                        <?php wp_nonce_field( $view['nonce_action'] ); ?>
                        <input name="action" type="hidden" value="wp2static_toggle_addon" />
                        <input name="addon_slug" type="hidden" value="<?php echo $addon->slug; ?>" />

                        <button><?php echo $addon->enabled ? 'Enabled' : 'Disabled'; ?></button>

                        </form>

                    </td>
                    <td>
                        <?php echo $addon->name; ?>
                        <br>
                        <?php echo $addon->description; ?>
                    </td>
                    <td><?php echo $addon->type; ?></td>
                    <td>
                        <a href="<?php echo $addon->docs_url; ?>"><span class="dashicons dashicons-book-alt"></span></a>
                    </td>
                    <td>
                        <a href="<?php echo esc_url( admin_url("admin.php?page={$addon->slug}") ); ?>"><span class="dashicons dashicons-admin-generic"></span></a>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>
</div>
