<h2>WP2Static > Diagnostics</h2>

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
            <td>Non-public dev server</td>
            <td>
                <p>Use our non-tracking service to check if your site is protected from public access.</p>

                <button class="button btn-primary">Check</button>
            </td>
            <td>WP2Static should be running on a non-production development server, protected from public access.</td>
        </tr>
        <tr>
            <td>Local DNS resolution</td>
            <td>
<?php

$dash_icon = 'dashicons-editor-help';
$color = 'gray';

switch($view['localDNSReslution']) {
    case '':
        $dash_icon = 'dashicons-editor-help';
        $color = '#FE8F25';

        break;
    case 'Private':
        $dash_icon = 'dashicons-yes';
        $color = '#3ad23a';

        break;
    case 'Public':
        $dash_icon = 'dashicons-no';
        $color = 'red';

        break;
}

?>
                <?php echo $view['localDNSReslution'] ? $view['localDNSReslution'] : 'Unknown'; ?>

                <span
                    class="dashicons <?php echo $dash_icon; ?>"
                    style="color: <?php echo $color; ?>;"
                ></span>
            </td>
            <td>Crawling your site will be faster if WP2Static doesn't have to go the long route when fetching URLs. Ensure your WordPress site's URL resolves locally.</td>
        </tr>
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
            <td>Uploads directory writable</td>
            <td>
                <?php echo $view['uploadsWritable']  ? 'Writable' : 'Non-writable'; ?>

                <span
                    class="dashicons <?php echo $view['uploadsWritable'] ? 'dashicons-yes' : 'dashicons-no'; ?>"
                    style="color: <?php echo $view['uploadsWritable'] ? 'green' : 'red'; ?>;"
                ></span>
            </td>
            <td>Generating a static site can involve long-running processes. Set your PHP max_execution_time setting to unlimited or find a better webhost if you're prevented from doing so.</td>
        </tr>
    </tbody>
</table> 


<b></b>


   <li><b></b>
     <span :style="siteInfo.maxExecutionTime == 0 ? 'color:#3ad23a;': 'color:red;'">
        {{ siteInfo.maxExecutionTime }} {{ siteInfo.maxExecutionTime == 0 ? '(Unlimited)': 'secs' }}
     </span>
    </li>
   <li><b>Writable uploads dir</b> <span v-if="siteInfo.uploadsWritable" class="dashicons dashicons-yes" style="color: #3ad23a;"></span></li>
</ul>

</div>

   <div v-if="siteInfo.phpOutOfDate" class="notice notice-error inline wp2static-notice">
      <h2 class="title">Outdated PHP version detected</h2>
      <p>The current officially supported PHP versions can be found on <a href="http://php.net/supported-versions.php" target="_blank">PHP.net</a></p>

      <p>Whilst the plugin tries to work on the most common PHP environments, it currently requires PHP 7.2 or higher.</p>

      <p>As official security support drops for PHP 5.6 at the end of 2018, it is strongly recommended to upgraded your WordPress hosting environment to PHP 7.2 or above.<br><br>For help on upgrading your environment, please join our support community at <a href="https://wp2static.com/community/" target="_blank">https://wp2static.com/community/</a></p>

      <p>Your current PHP version is: <?php echo PHP_VERSION; ?></p>
    </div>


   <div v-if="!siteInfo.uploadsWritable" class="notice notice-error inline wp2static-notice">
      <h2 class="title">Your uploads directory is not writable</h2>
      <p>Please ensure that <code>{{ siteInfo.uploads_path }}</code>
            is writable by your webserver.</p>
    </div>


   <div v-if="!siteInfo.curlSupported" class="notice notice-error inline wp2static-notice">
      <h2 class="title">You need the cURL extension enabled on your web server</h2>
        <p> This is a library that allows the plugin to better export your static site out to services like GitHub, S3, Dropbox, BunnyCDN, etc. It's usually an easy fix to get this working. You can try Googling "How to enable cURL extension for PHP", along with the name of the environment you are using to run your WordPress site. This may be something like DigitalOcean, GoDaddy or LAMP, MAMP, WAMP for your webserver on your local computer. If you're still having trouble, the developer of this plugin is easger to help you get up and running. Please ask for help on our <a href="https://forum.wp2static.com">forum</a>.</p>
    </div>


   <div v-if="!siteInfo.domDocumentAvailable" class="notice notice-error inline wp2static-notice">
      <h2 class="title">You're missing a required PHP library (DOMDocument)</h2>
        <p> This is a library that is used to parse the HTML documents when WP2Static crawls your site. It's usually an easy fix to get this working. You can try Googling "DOMDocument missing", along with the name of the environment you are using to run your WordPress site. This may be something like DigitalOcean, GoDaddy or LAMP, MAMP, WAMP for your webserver on your local computer. If you're still having trouble, the developer of this plugin is easger to help you get up and running. Please ask for help on our <a href="https://forum.wp2static.com">forum</a>.</p>
    </div>


   <div v-if="!siteInfo.permalinksDefined" class="notice notice-error inline wp2static-notice">
      <h2 class="title">You need to set your WordPress Pemalinks</h2>

        <p>Due to the nature of how static sites work, you'll need to have some kind of permalinks structure defined in your <a href="<?php echo admin_url( 'options-permalink.php' ); ?>">Permalink Settings</a> within WordPress. To learn more on how to do this, please see WordPress's official guide to the <a href="https://codex.wordpress.org/Settings_Permalinks_Screen">Settings Permalinks Screen</a>.</p>
    </div>

