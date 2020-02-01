<div id="form_settings" v-show="currentTab == 'form_settings'">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Form Processor', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <select name="form_processor" id="form_processor_select">
        <?php

        // move this all to JS:

        $form_processors = apply_filters(
            'wp2static_add_form_processor_option_to_ui',
            $form_processors
        );

        ?>

        <option value=''>Choose where to submit your forms to</option>
    </select>

    <p id="form_processor_description">Form processor description will appear here</p>

    <p id="form_processor_website"></p>

    <?php displayTextfield( $this, 'form_processor_endpoint', 'Form Endpoint', '', '' ); ?>


   </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Process Detected Forms', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>The following forms are supported on your site. Choose those which you want to send to your Form Processor</p>

    <?php displayCheckbox( $this, 'process_form_wordpress', 'WordPress standard' . $to ); ?>
    <?php displayCheckbox( $this, 'process_form_contactform7', 'Contact Form 7' . $to ); ?>
    <?php displayCheckbox( $this, 'process_form_gravity', 'Gravity Forms' . $to ); ?>
    <?php displayCheckbox( $this, 'process_form_elementor', 'Elementor' . $to ); ?>
    <?php displayCheckbox( $this, 'process_form_ninja', 'Ninja Forms' . $to ); ?>
   </div>
</section>

</div> <!-- end advanced settings -->
