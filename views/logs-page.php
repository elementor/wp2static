<br>

<table class="widefat striped">
    <thead>
        <tr>
            <th>When</th>
            <th>What</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach( $view['logs'] as $log ) : ?>
            <tr>
                <td><?php echo $log->time; ?></td>
                <td><?php echo $log->log; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

