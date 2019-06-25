<div class="production_deploy" style="display:none;">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Where will you host the optimized version of your site?', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <select class="selected_deployment_method" name="selected_deployment_option">
        <?php generateDeploymentMethodOptions(); ?>
    </select>
   </div>
</section>

<!-- legacy multi-export functionality relied on the baseUrl field being first in the settings block -->
<input style="display:none;" type="text" id="baseUrl" name="baseUrl" value="<?php echo esc_attr( $this->baseUrl ); ?>" size="50" placeholder="http://mystaticsite.com" />

<?php

  // load up each deployment settings block
  $deployment_option_templates = array(
      __DIR__ . '/folder_settings_block.php',
      __DIR__ . '/zip_settings_block.php',
  );

$deployment_option_templates = apply_filters(
    'wp2static_load_deploy_option_template',
    $deployment_option_templates
);

  foreach ( $deployment_option_templates as $deployment_option_template ) {
      require_once $deployment_option_template;
  }

    ?>

</div> <!-- end export_your_site -->
