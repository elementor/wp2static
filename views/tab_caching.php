<div id="caching_settings" v-show="currentTab == 'caching_settings'">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Crawl Caching', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>Don't recrawl files crawled within last n period.</p>

    <select name="crawl_caching_time_unit" id="crawl_caching">

    <?php
        // TODO: shift this into helper function for select
        $increments = array( 1, 5, 10, 25, 50, 100, 500, 1000, 999999 );

    foreach ( $increments as $increment ) :
        if ( $increment == 999999 ) : ?>
            <option value="999999"<?php echo $this->options->crawl_caching_time_unit == $increment ? ' selected' : ''; ?>>Maximum</option>
            <?php else : ?>
            <option value="<?php echo $increment; ?>"<?php echo $this->options->crawl_caching_time_unit == $increment ? ' selected' : ''; ?>><?php echo $increment; ?></option>
      
        <?php endif;
            endforeach; ?>

    </select>

    <select name="crawl_caching_time_period" id="crawl_caching_time_period">

        <?php
        // TODO: shift this into helper function for select
        $increments = [
            'Minutes',
            'Hours',
            'Days',
        ];

        foreach ( $increments as $increment ) :
            if ( $increment == 999999 ) : ?>
            <option value="999999"<?php echo $this->options->crawl_caching_time_period == $increment ? ' selected' : ''; ?>>Maximum</option>
        <?php else : ?>
            <option value="<?php echo $increment; ?>"<?php echo $this->options->crawl_caching_time_period == $increment ? ' selected' : ''; ?>><?php echo $increment; ?></option>
      
        <?php endif;
            endforeach; ?>
    
      </select>

        <?php $tpl->displayCheckbox( $this, 'dontUseCrawlCaching', 'Disregard cache and crawl everything' ); ?>

      <button v-on:click.prevent="deleteCrawlCache" class="wp2static-btn btn-sm mg-top10">Delete Crawl Cache</button>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Delete Deploy Cache', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <button v-on:click.prevent="deleteDeployCache"  type="button" class="btn-primary button">Delete deploy cache</button>

    <p>When deploying, WP2Static will check each file to see if it's changed since the last deployment. It will skip unchanged files based on this information. If you want to force an uncached deployment, click this button and any caches will be emptied, requiring a full deploy on the next run.</p>
  </div>
</section>


<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Generated Static Files', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <button id="check_generated_static_content" type="button" class="btn-primary button">Check generated static files</button>

    <p>Check the size on disk and number of files within your generated static site.</p>

    <ul>
        <li>Path:</li>
        <li>Size on disk:</li>
        <li>Number of files:</li>
    </ul>
  </div>

  <div class="content">
    <button id="delete_generated_static_content" type="button" class="btn-primary button">Delete generated static files</button>

    <p>This will delete the <code>wp2statric-exported-site</code> directory within your uploads directory.</p>
  </div>
</section>

</div> <!-- end advanced settings -->
