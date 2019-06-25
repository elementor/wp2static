<div class="automation_settings" style="display:none;">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Re-deploy when site changes', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>With Crawl and Deploy Caches enabled, only the files changed since your last deployment need processing. Choose which actions in WordPress will trigger a staging redeployment:</p>

    <?php $tpl->displayCheckbox( $this, 'redeployOnPostUpdates', 'When a post is created/updated' . $to ); ?>
   </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Email upon completion', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php
      $current_user = wp_get_current_user();
      $to = $current_user->user_email;
      $tpl->displayCheckbox( $this, 'completionEmail', 'Will send to: ' . $to ); ?>

    <p>Be alerted when your deployment process is complete.</p>
   </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Schedule deploys with WP-Cron', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>Use the <a href="" target="_blank">WP-Crontrol plugin</a> and WP2Static's hook named <code>wp_static_html_output_server_side_export_hook</code> to schedule automated static site generation and deployment to staging.</p>
   </div>
</section>

</div> <!-- end advanced settings -->
