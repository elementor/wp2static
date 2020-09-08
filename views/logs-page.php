<div class="wrap">
    <br>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>When</th>
                <th>What</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! $view['logs'] ) : ?>
                <tr>
                    <td colspan="2">Logs are empty.</td>
                </tr>
            <?php endif; ?>


            <?php foreach( $view['logs'] as $log ) : ?>
                <tr>
                    <td><?php echo $log->time; ?></td>
                    <td><?php echo $log->log; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br> 

    <?php if ( $view['logs'] ) : ?>
        <form
            name="wp2static-log-delete"
            method="POST"
            action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

        <?php wp_nonce_field( $view['nonce_action'] ); ?>
        <input name="action" type="hidden" value="wp2static_log_delete" />

        <button class="wp2static-button button btn-danger">Delete Log</button>

        </form>
    <?php endif; ?>
</div>
