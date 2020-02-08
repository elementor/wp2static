<?php
/**
 * @package WP2Static
 *
 * Copyright (c) 2011 Leon Stafford
 */

function displayTextfield($a = null, $b = null, $c = null, $d = null, $e = null) {
 echo 'something';
}

function displayCheckbox($a = null, $b = null, $c = null) {
 echo 'something';
}

?>
<form
    name="wp2static-ui-options"
    method="POST"
    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

<?php
// load core and add-on options templates
foreach($view['options_templates'] as $options_template) {
    require_once $options_template;
}
?>

<?php wp_nonce_field( $view['nonce_action'] ); ?>
<input name="action" type="hidden" value="wp2static_ui_save_options" />

<button class="button btn-primary" type="submit">Save options</button>

</form>
