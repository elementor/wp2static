<div class="url_detection" style="display:none;">

<section class="wp2static-content">
  <h2><?php echo __( 'Initial crawl list', 'static-html-output-plugin' ); ?></h2>
  <p>Before it starts to export your site, the plugin first generates a list of all WordPress URLs it thinks it should include. It takes these from what it knows about your posts, pages, tags, archives and media.</p>
  <div id="initial_crawl_list_loader" class="spinner is-active" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;">
    Generating initial file list
  </div>

  <p><em id="initial_crawl_list_count"></p>
 
  <p>
    <a id="preview_initial_crawl_list_button" style="display:none;" href="<?php echo $view['site_info']['uploads_url']; ?>wp2static-working-files/INITIAL-CRAWL-LIST.txt" class="wp2static-btn" target="_blank">Preview initial crawl list</a>
  </p>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Control Detected URLs', 'static-html-output-plugin' ); ?></h2>

    <button id="detectEverythingButton" class="wp2static-btn">Select all</button>
    <button id="detectNothingButton" class="wp2static-btn">Select none</button>
  </div>
  
  <div class="content">
    <p>Control which URLs from this WordPress site we want to use for our initial crawl list.</p>

    <p>Detecting less will result in faster crawling, but if you end up with missing URLs in your exported site, enable more options.</p>

    <?php

    $detection_options_table = [
        [
            false,
            'detectHomepage',
            'Homepage',
            'On by default, this will always include the <code>/</code> URL or site root.',
        ],
        [
            'checkbox',
            'detectPages',
            'Pages',
            'All published Pages. Use the date range option below to further filter.',
        ],
        [
            'checkbox',
            'detectPosts',
            'Posts',
            'All published Posts. Use the date range option below to further filter.',
        ],
        [
            'checkbox',
            'detectCustomPostTypes',
            'Custom Post Types',
            'Include URLs for all Custom Post Types.',
        ],
        [
            'checkbox',
            'detectFeedURLs',
            'Feed URLs',
            'RSS/Atom feeds, such as <code>mydomain.com/some-post/feed/</code>.',
        ],
        [
            'checkbox',
            'detectVendorCacheDirs',
            'Vendor cache',
            'Vendor cache dirs, as used by Autoptimize and certain themes to store images and assets.',
        ],
        [
            'checkbox',
            'detectAttachments',
            'Attachment URLs',
            'The additional URLs for attachments, such as images. Usually not needed.',
        ],
        [
            'checkbox',
            'detectArchives',
            'Archive URLs',
            'All Archive pages, such as Post Categories and Date Archives, etc.',
        ],
        [
            'checkbox',
            'detectPostPagination',
            'Posts Pagination',
            'Get all paginated URLs for Posts.',
        ],
        [
            'checkbox',
            'detectCategoryPagination',
            'Category Pagination',
            'Get all paginated URLs for Categories.',
        ],
        [
            'checkbox',
            'detectComments',
            'Comment URLs',
            'Get all URLs for Comments.',
        ],
        [
            'checkbox',
            'detectCommentPagination',
            'Comments Pagination',
            'Get all paginated URLs for Comments.',
        ],
        [
            'checkbox',
            'detectParentTheme',
            'Parent Theme URLs',
            'Get all URLs within Parent Theme dir.',
        ],
        [
            'checkbox',
            'detectChildTheme',
            'Child Theme URLs',
            'Get all URLs within Child Theme dir.',
        ],
        [
            'checkbox',
            'detectUploads',
            'Uploads URLs',
            'Get all public URLs for WP uploads dir.',
        ],
        [
            'checkbox',
            'detectPluginAssets',
            'Plugin Assets',
            'Detect all assets from within all plugin directories.',
        ],
        [
            'checkbox',
            'detectWPIncludesAssets',
            'WP-INC JS',
            'Get all public URLs for wp-includes assets.',
        ],
    ];
    ?>

  <table id="detectionOptionsTable">
        <?php foreach ( $detection_options_table as $detection_option ) : ?>
          <tr>
              <td>
                  <label for='<?php echo $detection_option[1]; ?>'>
                      <b><?php echo $detection_option[2]; ?></b>
                  </label>
              </td>
              <td>
              <?php

                if ( $detection_option[0] ) {
                    $tpl->displayCheckbox( $this, $detection_option[1], $detection_option[3] );

                } else {
                    echo '&nbsp;';
                }

                ?>
              </td>

          <tr>
        <?php endforeach; ?>
  </table>

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

    <textarea class="wp2static-textarea" name="additionalUrls" id="additionalUrls" rows="5" cols="10"><?php echo $this->options->additionalUrls ? $this->options->additionalUrls : ''; ?></textarea>

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
