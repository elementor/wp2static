<?php
/**
 * @package WP2Static
 *
 * Copyright (c) 2011 Leon Stafford
 */

$ajax_nonce = wp_create_nonce( 'wpstatichtmloutput' );

function displayTextfield($a = null, $b = null, $c = null, $d = null, $e = null) {
 echo 'something';
}

function displayCheckbox($a = null, $b = null, $c = null) {
 echo 'something';
}

?>
<h2>WP2Static > Options</h2>

<form
    name="wp2static-ui-options"
    method="POST"
    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

<h2>Quick nav</h2>

<ul>
    <li><a href="#core-detection-options">Detection Options</a></li>
    <li><a href="#core-crawling-options">Crawling Options</a></li>
    <li><a href="#core-post-processing-options">Post-processing Options</a></li>
    <li><a href="#core-deployment-options">Deployment Options</a></li>
</ul>

<?php
// load core and add-on options templates
foreach($view['options_templates'] as $options_template) {
    require_once $options_template;
}
?>

<?php wp_nonce_field( $view['nonce_action'] ); ?>
<input name="action" type="hidden" value="wp2static_ui_save_options" />

<button class="button btn-primary" type="submit">Save settings</button>

</form>
