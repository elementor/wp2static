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


<!-- main form containing options that get sent -->
<form id="general-options" method="post" action="#" v-on:submit.prevent>

<div class="wp2static-content-wrapper">

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

</div>

<span class="submit" style="display:none;">
    <?php wp_nonce_field( $view['onceAction'] ); ?>
  <input id="basedir" type="hidden" name="basedir" value="" />
  <input id="subdirectory" type="hidden" name="subdirectory" value="<?php echo $view['site_info']->subdirectory; ?>" />
  <input id="hiddenNonceField" type="hidden" name="nonce" value="<?php echo $ajax_nonce; ?>" />
</span>
</form>
