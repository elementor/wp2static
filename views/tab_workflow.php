<div class="workflow_tab">

<section class="wp2static-content text-center">
  <img class="welcome" src="<?php echo plugins_url( '/../assets/welcome.svg', __FILE__ ); ?>" alt="Welcome">

  <p class="lead">
    <?php echo __( 'Congratulations on choosing to', 'static-html-output-plugin' ); ?>
        <strong><?php echo __( 'Go Static!', 'static-html-output-plugin' ); ?></strong>
  </p>

  <p>
    <?php echo __(
         'We think it\'s the best way to deal with 90% of WordPress sites,<br> but the concept does take a little while to get used to.',
          'static-html-output-plugin'
    ); ?>
  </p>

  <p style="margin:2  em 0">
    <strong>
        <?php echo __('Try a 1-click deployment option below or', 'static-html-output-plugin');?> <a id="GoToDeployTabLink" href="#"><?php echo __('list available deploy methods', 'static-html-output-plugin');?></a>.
    </strong></p>

  <a href="#" id="GenerateZIPOfflineUse" class="wp2static-btn btn-lg">
    <?php echo __('Generate ZIP for Offline Use', 'static-html-output-plugin');?>
  </a>

  <a href="#" id="GenerateZIPDeployAnywhere" class="wp2static-btn btn-lg">
    <?php echo __('Generate ZIP to Deploy Anywhere', 'static-html-output-plugin');?>
  </a>

  <a href="#" id="GoToDeployTabLink" class="wp2static-btn btn-lg pink">
    <?php echo __('Other Deployments', 'static-html-output-plugin');?>
  </a>
</section>

</div> <!-- end workflow settings -->
