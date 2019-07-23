<div id="help_troubleshooting" v-show="currentTab == 'help_troubleshooting'">

<section class="wp2static-content wp2static-flex">
  <div class="content">
    <h2><span class="dashicons dashicons-book-alt"></span> <?php echo __( 'Documentation', 'static-html-output-plugin' ); ?></h2>
    <p><?php echo __( 'Learn how to getting started with WP2Static:', 'static-html-output-plugin' ); ?></p>
    <ul>
      <li><a href="https://wp2static.com/how-wp2static-works" target="_blank">How WP2Static Works</a></li>
      <li><a href="https://wp2static.com/system-requirements" target="_blank">System Requirements</a></li>
      <li><a href="https://wp2static.com/preparing-to-go-static-with-wordpress" target="_blank">Preparing to go Static with WordPress</a></li>
      <li><a href="https://wp2static.com/doing-a-test-export" target="_blank">Doing a Test Export</a></li>
      <li><a href="https://wp2static.com/contact-forms-for-static-sites/" target="_blank">Contact Forms for Static Websites</a></li>
      <li><a href="https://wp2static.com/search-options-for-static-sites/" target="_blank">Search Options for Static Sites</a></li>
    </ul>
    <a href="https://wp2static.com/documentation" class="wp2static-btn btn-sm" target="_blank">Visit our Documentation</a>
  </div>

  <div class="content">
    <h2><i class="dashicons dashicons-video-alt3"></i> <?php echo __( 'Tutorial', 'static-html-output-plugin' ); ?></h2>
    <p><?php echo __( 'A good introduction to static site benefits for WordPress users.', 'static-html-output-plugin' ); ?></p>
    <a href="https://www.youtube.com/watch?v=HPc4JjBvkrU" target="_blank">
      <img src="<?php echo plugins_url( '/../assets/vidthumb.jpg', __FILE__ ); ?>" alt="YouTube"><br>
      <span class="wp2static-btn btn-sm mg-top10"><?php echo __( 'Watch Video on YouTube', 'static-html-output-plugin' ); ?></span>
    </a>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content">
    <h2><i class="dashicons dashicons-sos"></i> <?php echo __( 'Create Support Request', 'static-html-output-plugin' ); ?></h2>

    <div class="wp2s-form">
    <?php

      // default support request to user email
      global $current_user;
      get_currentuserinfo();

    ?>
    <div class="control">
      <label for="supportRequestEmail">
        <?php echo __( 'Your email', 'static-html-output-plugin' ); ?>
      </label>

      <input type="text" id="supportRequestEmail" name="supportRequestEmail" value="<?php echo $current_user->user_email; ?>" size="50">
    </div>

    <div class="control">
      <label for="supportRequestContent">
        <?php echo __( 'Your issue', 'static-html-output-plugin' ); ?>
      </label>

      <textarea class="wp2static-textarea" name="supportRequestContent" id="supportRequestContent" rows="5" cols="10">
Example:

Help! I'm having trouble with exporting my site - it's missing some images and fonts.
I'm hosting with Acme Company. I'm new to WordPress.
Attached is my Debug Log to give you more info.
      </textarea>
    </div>

    </div>
  </div>

  <div class="content">
    <div class="wp2s-form">
      <div class="control">
      <p>
        <?php echo __( 'Get help from the WP2Static team - we\'ll open a ticket for you and follow-up soon!', 'static-html-output-plugin' ); ?>
      </p>

            <?php $tpl->displayCheckbox( $this, 'supportRequestIncludeLog', 'Include Debug Log (helps us diagnose quicker)' ); ?>
      </div>

      <p>Unless you choose to send us your export log (really helps!), we won't collect any other information about you, your server, etc. You can verify the code we are using for this plugin at: <a href="https://github.com/WP2Static/wp2static" target="_blank">github.com/WP2Static/wp2static</a></p>

      <p>We send this request via <a href="https://zapier.com" target="_blank">Zapier</a>, which then creates a ticket in our helpdesk software, <a href="https://zammad.com" target="_blank">Zammad</a>.</p>

      <div class="control">
        <button id="send_support_request" class="wp2static-btn">
            <?php echo __( 'Create New Support Request', 'static-html-output-plugin' ); ?>
          </button>
      </div>

      <p><?php echo __( 'Check the status of your ticket or create one via the web', 'static-html-output-plugin' ); ?>: <a href="https://wp2static.zammad.com" target="_blank">wp2static.zammad.com</a>.</p>
    </div>
  </div>
</section>

</div> <!-- end help settings -->
