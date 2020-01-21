<?php
/**
 * @package WP2Static
 *
 * Copyright (c) 2011 Leon Stafford
 */

$ajax_nonce = wp_create_nonce( 'wpstatichtmloutput' );

$tpl = new \WP2Static\TemplateHelper();

?>


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

?>

<div class="wp2static-content-wrapper">

<?php require_once __DIR__ . '/tab_detection.php'; ?>
<?php require_once __DIR__ . '/tab_crawling.php'; ?>
<?php require_once __DIR__ . '/tab_processing.php'; ?>
<?php require_once __DIR__ . '/tab_forms.php'; ?>
<?php require_once __DIR__ . '/tab_advanced.php'; ?>
<?php require_once __DIR__ . '/tab_staging.php'; ?>
<?php require_once __DIR__ . '/tab_caching.php'; ?>
<?php require_once __DIR__ . '/tab_automation.php'; ?>

</div>

<span class="submit" style="display:none;">
    <?php wp_nonce_field( $view['onceAction'] ); ?>
  <input id="basedir" type="hidden" name="basedir" value="" />
  <input id="subdirectory" type="hidden" name="subdirectory" value="<?php echo $view['site_info']->subdirectory; ?>" />
  <input id="hiddenNonceField" type="hidden" name="nonce" value="<?php echo $ajax_nonce; ?>" />
</span>
</form>
