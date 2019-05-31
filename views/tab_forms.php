<div class="form_settings" style="display:none;">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Form processor', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <select name="form_Processor" id="form_Processor_select">
        <?php

        // move this all to JS:

        $form_processors = apply_filters(
            'wp2static_add_form_processor_option_to_ui',
            $form_processors
        );

        ?>

        <option value=''>Choose where to submit your forms to</option>
    </select>

  <div class="content">
    <p id="form_processor_description">Form processor description will appear here</p>

    <?php $tpl->displayTextfield( $this, 'form_endpoint', 'Form Endpoint', '', '' ); ?>


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
