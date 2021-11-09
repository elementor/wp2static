<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded
// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @var mixed[] $view
 */
?>

<div class="wrap">
    <br>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Health check</th>
                <th>Status</th>
                <th>Advice</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>PHP max_execution_time</td>
                <td>
                    <?php echo $view['maxExecutionTime'] == 0 ? 'Unlimited' : $view['maxExecutionTime'] . ' secs'; ?>

                    <span
                        class="dashicons <?php echo $view['maxExecutionTime'] == 0 ? 'dashicons-yes' : 'dashicons-no'; ?>"
                        style="color: <?php echo $view['maxExecutionTime'] == 0 ? 'green' : 'red'; ?>;"
                    ></span>
                </td>
                <td>Generating a static site can involve long-running processes. Set your PHP max_execution_time setting to unlimited or find a better webhost if you're prevented from doing so.</td>
            </tr>
            <tr>
                <td>PHP memory_limit</td>
                <td>
                    <?php echo $view['memoryLimit']; ?>

                </td>
                <td>WP2Static will use as much memory as is available to it during processing. Allocating more of your system RAM to PHP should improve performance.</td>
            </tr>
            <tr>
                <td>Uploads directory writable</td>
                <td>
                    <?php echo $view['uploadsWritable'] ? 'Writable' : 'Non-writable'; ?>

                    <span
                        class="dashicons <?php echo $view['uploadsWritable'] ? 'dashicons-yes' : 'dashicons-no'; ?>"
                        style="color: <?php echo $view['uploadsWritable'] ? 'green' : 'red'; ?>;"
                    ></span>
                </td>
                <td>By default WP2Static writes the generated static site under wp-content/uploads directory. Make sure WP2Static has the permission to do so.</td>
            </tr>
            <tr>
                <td>PHP version</td>
                <td>
                    <?php echo PHP_VERSION; ?>

                    <span
                        class="dashicons <?php echo ! $view['phpOutOfDate'] ? 'dashicons-yes' : 'dashicons-no'; ?>"
                        style="color: <?php echo ! $view['phpOutOfDate'] ? 'green' : 'red'; ?>;"
                    ></span>
                </td>
                <td>
                <p>The current officially supported PHP versions can be found on <a href="http://php.net/supported-versions.php" target="_blank">PHP.net</a></p>

                <p>WP2Static now requires a minimum of PHP 7.4 and recommends PHP 8.0 for better performance. If your hosting provider doesn't provide PHP 8.0 or at least PHP 7.4, find a better one!</p>
                </td>
            </tr>
            <tr>
                <td>cURL extension loaded</td>
                <td>
                    <?php echo $view['curlSupported'] ? 'Yes' : 'No'; ?>

                    <span
                        class="dashicons <?php echo $view['curlSupported'] ? 'dashicons-yes' : 'dashicons-no'; ?>"
                        style="color: <?php echo $view['curlSupported'] ? 'green' : 'red'; ?>;"
                    ></span>
                </td>
                <td>
                    <p>You need the cURL extension enabled on your web server</p>

                    <p> This is a library that allows the plugin to better export your static site out to services like GitHub, S3, Dropbox, BunnyCDN, etc. It's usually an easy fix to get this working. You can try Googling "How to enable cURL extension for PHP", along with the name of the environment you are using to run your WordPress site. This may be something like DigitalOcean, GoDaddy or LAMP, MAMP, WAMP for your webserver on your local computer. If you're still having trouble, the developer of this plugin is easger to help you get up and running. Please ask for help on our <a href="https://forum.wp2static.com">forum</a>.</p>
                </td>
            </tr>
            <tr>
                <td>WordPress Permalinks Compatible</td>
                <td>
                    <?php echo $view['permalinksAreCompatible'] ? 'Yes' : 'No'; ?>

                    <span
                        class="dashicons <?php echo $view['permalinksAreCompatible'] ? 'dashicons-yes' : 'dashicons-no'; ?>"
                        style="color: <?php echo $view['permalinksAreCompatible'] ? 'green' : 'red'; ?>;"
                    ></span>
                </td>
                <td>
                    <p>Due to the nature of how static sites work, you'll need to have some kind of permalinks structure defined in your <a href="<?php echo admin_url( 'options-permalink.php' ); ?>">Permalink Settings</a> within WordPress. To learn more on how to do this, please see WordPress's official guide to the <a href="https://codex.wordpress.org/Settings_Permalinks_Screen">Settings Permalinks Screen</a>. The permalinks must end in a trailing slash (/).</p>
                </td>
            </tr>
        </tbody>
    </table>


    <h4>Loaded PHP extensions</h4>

    <table class="widefat striped">
        <tbody>

    <?php
    natcasesort( $view['extensions'] );
    $ar_list = $view['extensions'];
    $rows = (int) ceil( count( $ar_list ) / 5 );
    $lists  = array_chunk( $ar_list, $rows );

    foreach ( $lists as $column ) {
        echo '<tr>';
        foreach ( $column as $item ) {
            echo '<td>' . $item . '</td>';
        }
        echo '</tr>';
    }

    ?>
        </tbody>
    </table>

    <h4>WP2Static Core Options</h4>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ( $view['coreOptions'] as $option ) : ?>

            <tr>
            <td><?php echo $option->label; ?></td>
            <td><?php echo $option->value; ?></td>
            </tr>

            <?php endforeach; ?>

        </tbody>
    </table>

    <h4>WordPress Site Info</h4>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>

            <?php
            // TODO: sort site infos alpha
            foreach ( $view['site_info'] as $name => $value ) : ?>
            <tr>
            <td><?php echo $name; ?></td>
            <td><?php echo $value; ?></td>
            </tr>

            <?php endforeach; ?>

        </tbody>
    </table>

    <div style="display:none;">
        <b>TODO: load add-on diagnostics here, via filter</b>

        ie

        <p>PHP DOMDocument library available</p>
        <code>        $view['domDocumentAvailable'] = class_exists( 'DOMDocument' );</code>
        <h2 class="title">You're missing a required PHP library (DOMDocument)</h2>
        <p> This is a library that is used to parse the HTML documents when WP2Static crawls your site. It's usually an easy fix to get this working. You can try Googling "DOMDocument missing", along with the name of the environment you are using to run your WordPress site. This may be something like DigitalOcean, GoDaddy or LAMP, MAMP, WAMP for your webserver on your local computer. If you're still having trouble, the developer of this plugin is easger to help you get up and running. Please ask for help on our <a href="https://forum.wp2static.com">forum</a>.</p>
    </div>
</div>
