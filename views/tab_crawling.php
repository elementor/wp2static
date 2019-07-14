<div id="crawl_settings" v-show="currentTab == 'crawl_settings'">

<section-with-checkbox
    id="includeDiscoveredAssets"
    :title="fieldData.includeDiscoveredAssets.title"
    :description="fieldData.includeDiscoveredAssets.description"
    :hint="fieldData.includeDiscoveredAssets.hint"
    :checked="includeDiscoveredAssets"
></section-with-checkbox>

<section-with-checkbox
    id="useBasicAuth"
    :title="fieldData.useBasicAuth.title"
    :description="fieldData.useBasicAuth.description"
    :hint="fieldData.useBasicAuth.hint"
    :checked="useBasicAuth"
></section-with-checkbox>

<section class="wp2static-content wp2static-flex no-tb-bs pd-top0">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Basic auth user', 'static-html-output-plugin' ); ?></h2>
  </div>
  <div class="content">
    <?php $tpl->displayTextfield( $this, 'basicAuthUser', 'Basic Auth user', '', '' ); ?>
  </div>
</section>

<section class="wp2static-content wp2static-flex no-tb-bs pd-top0">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Basic auth password', 'static-html-output-plugin' ); ?></h2>
  </div>
  <div class="content">
    <?php $tpl->displayTextfield( $this, 'basicAuthPassword', 'Basic Auth password', '', 'password' ); ?>
  </div>
</section>

<section class="wp2static-content wp2static-flex no-tb-bs pd-top0">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Custom crawling port', 'static-html-output-plugin' ); ?></h2>
  </div>
  <div class="content">
    <?php $tpl->displayTextfield( $this, 'crawlPort', 'Custom crawling port', '', 'password' ); ?>
  </div>
</section>

<section class="wp2static-content wp2static-flex no-tb-bs pd-top0">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Custom crawling user-agent', 'static-html-output-plugin' ); ?></h2>
  </div>
  <div class="content">
    <?php $tpl->displayTextfield( $this, 'crawlUserAgent', 'Custom crawling user-agent', '' ); ?>
    <p><em>Override the request HTTP header User-Agent (defaults to WP2Static.com).</em></p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Crawl Delay', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <select name="crawl_delay" id="crawl_delay">

    <?php
      // TODO: shift this into helper function for select
      $delays = array( 0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1, 2, 3, 4, 10 );

    foreach ( $delays as $delay ) : ?>
              <option value="<?php echo $delay; ?>"<?php echo $this->options->crawl_delay == $delay ? ' selected' : ''; ?>><?php echo $delay; ?></option>
        <?php endforeach; ?>
    
    </select>

    <p>This is set to 0, by default, for better performance, but if exports are failing and no error appears with Debug Log enabled or you see "Too many files open" or such errors in the server logs, try increasing this value until you get a successful export.</p>
  </div>
</section>

</div> <!-- end crawling settings -->
