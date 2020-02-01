<a name="core-detection-options"></a>

<hr>

<h2>Detection Options</h2>

<div id="url_detection" v-show="currentTab == 'url_detection'">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:66%">
    <h2><?php echo __( 'Control Detected URLs', 'static-html-output-plugin' ); ?></h2>

    <button v-on:click.prevent="detectEverything" class="wp2static-btn">Select all</button>
    <button v-on:click.prevent="detectNothing" class="wp2static-btn">Select none</button>
    <p>Control which URLs from this WordPress site we want to use for our initial crawl list.</p>

    <p>Detecting less will result in faster crawling, but if you end up with missing URLs in your exported site, enable more options.</p>

  <table id="detectionOptionsTable">

        <detection-checkbox 
            v-for="checkbox in detectionCheckboxes"
            v-bind:key="checkbox.id" 
            :id="checkbox.id"
            :title="checkbox.title"
            :description="checkbox.description"
        > </detection-checkbox>

  </table>
  </div>
  
  <div class="content">
    <p>Showing % n % URLs out of % y% (show all | show n)<p>

    <textarea style="width:100%;">Show first 50 URLs here by default</textarea>


  <p><i>Save options to reload the page and see the effect of your detection options</i></p>



  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content">
    <h2><?php echo __( 'Filter detected URLs', 'static-html-output-plugin' ); ?></h2>

    <p>After checking the Initial Crawl List, add any paths to filter out here.</p>

    <p>WP2Static automatically filters out common backup plugin directories, but please review your initial crawl list to ensure no unwanted URLs are detectected.</p>
  </div>

  <div class="content">
    <p>You can enter this as a partial string or full path (wildcards/regex not currently supported)</p>

    <pre>
      <code>.zip</code>
      <code>768x768.jpg</code>
      <code><?php echo $view['site_info']['site_url']; ?>/wp-content/themes/twentyseventeen/banana.jpg</code>
      <code>/my_pricelist.pdf</code>
    </pre>

    <textarea class="wp2static-textarea" name="excludeURLs" id="excludeURLs" rows="5" cols="10"><?php echo $this->options->excludeURLs ? $this->options->excludeURLs : ''; ?></textarea>

    <p><em>Save options to reload the page and see the effect of your detection options</em></p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content">
    <h2><?php echo __( 'Include additional URLs', 'static-html-output-plugin' ); ?></h2>
    <p>Where the plugin fails to detect certain URLs that you know you want to include, please add these here. This will be applied after any exclusions, in case you want to exclude a whole directory, then include just one file from it.</p>
  </div>

  <div class="content">
    <p><em>Supported formats are relative URLs</em></p>

    <pre>
      <code><?php echo $view['site_info']['site_url']; ?></code>
      <code><?php echo $view['site_info']['site_url']; ?>/wp-content/themes/twentyseventeen/banana.jpg</code>
      <code>my_pricelist.pdf</code>
    </pre>

    <textarea class="widefat" name="additionalUrls" id="additionalUrls" rows="5" cols="10"><?php echo $this->options->additionalUrls ? $this->options->additionalUrls : ''; ?></textarea>
  </div>
</section>
</div><!-- end crawl_setings -->
