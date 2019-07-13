<?php
/**
 * @package WP2Static
 *
 * Copyright (c) 2011 Leon Stafford
 */

$ajax_nonce = wp_create_nonce( 'wpstatichtmloutput' );

$tpl = new \WP2Static\TemplateHelper();

?>


<div id="vueApp">
    <div class="wrap wp2static">

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


      <nav class="nav-tab-wrapper">

        <a
            v-for="tab in tabs"
            v-bind:key="tab.id"
            v-on:click.prevent="changeTab2"
            :tabid="tab.id"
            class="nav-tab"
            v-bind:class="{ 'nav-tab-active': tab.id === currentTab }"
            href="#"
        >{{ tab.name }}</a>

      </nav>


      <!-- main form containing options that get sent -->
      <form id="general-options" method="post" action="#" v-on:submit.prevent>

        <?php

        function generateDeploymentMethodOptions() {
            $options = array(
                'folder' => array( 'Subdirectory on current server' ),
                'zip' => array( 'ZIP archive (.zip)' ),
            );

            $options = apply_filters(
                'wp2static_add_deployment_method_option_to_ui',
                $options
            );

            foreach ( $options as $key => $value ) {
                echo "<option value='$key'>$value[0]</option>";
            }
        }

        function generateDeploymentMethodOptionsProduction() {
            $options = array(
                'folder' => array( 'Subdirectory on current server' ),
                'zip' => array( 'ZIP archive (.zip)' ),
            );

            $options = apply_filters(
                'wp2static_add_deployment_method_option_to_ui',
                $options
            );

            foreach ( $options as $key => $value ) {
                echo "<option value='$key'>$value[0]</option>";
            }
        }

        ?>

        <div class="wp2static-content-wrapper">

        <?php require_once __DIR__ . '/tab_workflow.php'; ?>
        <?php require_once __DIR__ . '/tab_detection.php'; ?>
        <?php require_once __DIR__ . '/tab_crawling.php'; ?>
        <?php require_once __DIR__ . '/tab_processing.php'; ?>
        <?php require_once __DIR__ . '/tab_forms.php'; ?>
        <?php require_once __DIR__ . '/tab_advanced.php'; ?>
        <?php require_once __DIR__ . '/tab_staging.php'; ?>
        <?php require_once __DIR__ . '/tab_production.php'; ?>
        <?php require_once __DIR__ . '/tab_caching.php'; ?>
        <?php require_once __DIR__ . '/tab_automation.php'; ?>
        <?php require_once __DIR__ . '/tab_add_ons.php'; ?>
        <?php require_once __DIR__ . '/tab_help.php'; ?>

        </div>

        <span class="submit" style="display:none;">
            <?php wp_nonce_field( $view['onceAction'] ); ?>
          <input id="hiddenActionField" type="hidden" name="action" value="wp_static_html_output_ajax" />
          <input id="basedir" type="hidden" name="basedir" value="" />
          <input id="subdirectory" type="hidden" name="subdirectory" value="<?php echo $view['site_info']->subdirectory; ?>" />
          <input id="hiddenNonceField" type="hidden" name="nonce" value="<?php echo $ajax_nonce; ?>" />
          <input id="hiddenAJAXAction" type="hidden" name="ajax_action" value="" />
        </span>
      </form>


        <div id="wp2static-footer">

              <div class="inside">

                <div class="submit">
                    <!-- NOTE: removing extra nonce here, check why it was being used.. -->
                    <?php // wp_nonce_field( $view['onceAction'] ); ?>
                  <button :disabled="progress" v-on:click="generateStaticSite" class="wp2static-btn blue" id="wp2staticGenerateButton">
                    <?php echo __( 'Generate', 'static-html-output-plugin' ); ?>
                  </button>
                  <button :disabled="progress" v-on:click="startExport" class="wp2static-btn blue">
                    <?php echo __( 'Deploy to Staging', 'static-html-output-plugin' ); ?>
                  </button>
                  <button :disabled="progress" id="deployToProductionButton" class="wp2static-btn blue">
                    <?php echo __( 'Deploy to Production', 'static-html-output-plugin' ); ?>
                  </button>
                  <button :disabled="progress" v-on:click="saveOptions" class="wp2static-btn">
                    <?php echo __( 'Save Current Options', 'static-html-output-plugin' ); ?>
                  </button>
                  <button :disabled="progress" v-on:click="resetDefaults" class="wp2static-btn">
                    <?php echo __( 'Reset to Default Settings', 'static-html-output-plugin' ); ?>
                  </button>
                  <button v-if="progress" v-on:click="cancelExport" class="wp2static-btn orange">
                    <?php echo __( 'Cancel Export', 'static-html-output-plugin' ); ?>
                  </button>

                  <!-- TODO: set action to grab ZIP download URL from button vs anchor -->
                  <button id="downloadZIP" class="wp2static-btn btn-call-to-action">
                    <?php echo __( 'Download ZIP', 'static-html-output-plugin' ); ?>
                  </button>

                  <a href="#" class="wp2static-btn btn-call-to-action" target="_blank" id="goToMyStaticSite" style="display:none;">
                    <?php echo __( 'Open Deployed Site', 'static-html-output-plugin' ); ?>
                  </a>

                  <div id="export_timer"></div>

                </div> <!-- end submit -->

            </div> <!-- end inside -->


                <div id="pbar-container">
                    <div id="pbar-fill">

                    </div>

                    <div id="progress-container">
                      <div id="progress">
                        <div v-if="progress" id="pulsate-css"></div>
                        <div id="current_action">
                            {{ currentAction }}
                        </div>
                      </div>

                      <p id="exportDuration" style="display:block;"></p>
                    </div>
                </div>

        </div><!-- end wp2static-footer -->
    </div> <!-- end wrap wp2static -->
</div><!-- end vueApp -->

