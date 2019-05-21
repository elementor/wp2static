<div class="advanced_settings" style="display:none;">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Deployment Batch Size', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <select name="deployBatchSize" id="deployBatchSize">
  
        <?php
        // TODO: shift this into helper function for select
        $increments = array( 1, 5, 10, 25, 50, 100, 500, 1000, 999999 );

        foreach ( $increments as $increment ) :
            if ( $increment == 999999 ) : ?>
                <option value="999999"<?php echo $this->options->deployBatchSize == $increment ? ' selected' : ''; ?>>Maximum</option>
        <?php else : ?>
                  <option value="<?php echo $increment; ?>"<?php echo $this->options->deployBatchSize == $increment ? ' selected' : ''; ?>><?php echo $increment; ?></option>

        <?php endif;
              endforeach; ?>
    </select>

    <p>This is set to 1, by default, in order to avoid execution limit timeouts on restricted environments, such as shared hosting servers. Each increment is the amount of files the server will try to deploy on each request. Incrementing this will speed up your exports, by processing more are a time. If your export is failing, due to execution limits or API rate limits being reached, try setting this to a lower number.</p>    
   </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Re-deploy when site changes', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>With Crawl and Deploy Caches enabled, only the files changed since your last deployment need processing. Choose which actions in WordPress will trigger a redeployment:</p>

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
    <h2><?php echo __( 'Show deploy widget on WP dashboard', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>Show a widget on your WordPress dashboard for quickly triggering a manual deploy and showing recent deploy information.</p>

    <?php $tpl->displayCheckbox( $this, 'displayDashboardWidget', 'Enable WP2Static dashboard widget' . $to ); ?>
   </div>
</section>


<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'API Request Delay', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <select name="delayBetweenAPICalls" id="delayBetweenAPICalls">

    <?php
      // TODO: shift this into helper function for select
      $increments = array( 0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1, 2, 3, 4, 10 );

    foreach ( $increments as $increment ) : ?>
                <option value="<?php echo $increment; ?>"<?php echo $this->options->delayBetweenAPICalls == $increment ? ' selected' : ''; ?>><?php echo $increment; ?></option>
        <?php endforeach; ?>

    </select>

    <p>This is set to 0, by default, but if your deploy is hitting the remote API too rapidly for their rate limit, you can increase this to add a delay between each API request.</p>    
  </div>
</section>




</div> <!-- end advanced settings -->
