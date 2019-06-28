<div id="processing_settings" v-show="currentTab == 'processing_settings'">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Use document-relative URLs', 'static-html-output-plugin' ); ?></h2>
  </div>
  
  <div class="content">
    <?php $tpl->displayCheckbox( $this, 'useDocumentRelativeURLs', 'Use document-relative URLs' ); ?>

    <p>URLs in the exported site will be rewritten as <a href="https://www.w3schools.com/tags/tag_base.asp" target="_blank">relative URLs</a>. ie, <code>http://mydomain.com/some_dir/some_file.jpg</code> will become <code>some_dir/some_file.jpg</code></p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Use site-root relative URLs', 'static-html-output-plugin' ); ?></h2>
  </div>
  
  <div class="content">
    <?php $tpl->displayCheckbox( $this, 'useSiteRootRelativeURLs', 'Use site root-relative URLs' ); ?>

    <p>URLs in the exported site will be rewritten as site root-relative. ie, <code>http://mydomain.com/some_dir/some_file.jpg</code> will become <code>/some_dir/some_file.jpg</code></p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Allow offline usage', 'static-html-output-plugin' ); ?></h2>
  </div>
  
  <div class="content">
    <?php $tpl->displayCheckbox( $this, 'allowOfflineUsage', "Check this if you're going to run your site locally, ie on a USB drive given to a client." ); ?>

    <p>Destination URL will be ignored. Must combine with Document-Relative URLs option. <code>index.html</code> will be appended to all directory paths</p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Base HREF', 'static-html-output-plugin' ); ?></h2>
  </div>
  
  <div class="content">
    <?php $tpl->displayTextfield( $this, 'baseHREF', 'Base HREF', '', '' ); ?>

    <p>Setting this will tell the browser to resolve all URLs using the URL you set (<code>https://mydomain.com</code>, <code>/</code>, etc).</p>

    <p><b>Note:</b>If you want to deploy your static site to work in a subdirectory of any domain, set your Destination URL to the same as your Site URL and choose Use Document Relative URLs.</p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Custom rewrites in exported source code', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>In order to hide any trace that your site uses WordPress, you can choose to rewrite common URL structures, such as wp-content/themes, etc.</p>
    <p>You can also rewrite external URLs or arbitray text. WP2Static rewrites exactly what you specify as search and replace strings.</p>
    <p>Replace, for example, default WordPress paths within the source code</p> <br>
    <p>use the full path, such as:</p>

    <pre>
      <code>wp-content/themes/twentyseventeen/,contents/ui/mytheme/</code>
      <code>wp-includes/,inc/</code>
    </pre>

    <p>The plugin does its best to sort rewrite rules in order needed, to process the longest items first.</p>

    <textarea class="wp2static-textarea" name="rewrite_rules" id="rewrite_rules" rows="5" cols="10"><?php echo $this->options->rewrite_rules ? $this->options->rewrite_rules : ''; ?></textarea>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Rename Exported Directories', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>Required if rewriting any local URLs within the site.</p>
    <p>Set the source directory, then a comma and the target directory.</p>
    <p>In order to move wp-content/themes/twentyseventeen to contents/ui/mytheme, we'd need:</p>

    <pre>
      <code>wp-content,contents</code>
      <code>contents/themes,contents/ui</code>
      <code>contents/ui/twentyseventeen,contents/ui/mytheme</code>
      <code>wp-includes/,inc/</code>
    </pre>

    <textarea class="widefat" name="rename_rules" id="rename_rules" rows="5" cols="10"><?php echo $this->options->rename_rules ? $this->options->rename_rules : ''; ?></textarea>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Remove cruft', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php $tpl->displayCheckbox( $this, 'removeConditionalHeadComments', 'Remove conditional comments within HEAD', 'checked' ); ?>

    <p>Mostly obsolete, previously used for detecting versions of Internet Explorer and serving different CSS or JS.</p>

    <?php $tpl->displayCheckbox( $this, 'removeWPMeta', 'Remove WP Meta tags' ); ?>

    <p>The <code>&lt;meta&gt; name="generator" content="WordPress 4.9.8" /&gt;</code> type tags.</p>

    <?php $tpl->displayCheckbox( $this, 'removeWPLinks', 'Remove WP &lt;link&gt; tags' ); ?>

    <p>ie, <code>&lt;link& rel="EditURI"...</code> type tags that usually aren't needed.</p>

    <?php $tpl->displayCheckbox( $this, 'removeHTMLComments', 'Remove HTML comments' ); ?>

    <p>ie, <code>&lt;!-- / Yoast SEO plugin. --&gt;</code> type comments that are ridiculously wasting bytes</p>

    <?php $tpl->displayCheckbox( $this, 'removeCanonical', 'Remove Canonical tags from pages (best left unchecked)' ); ?>

    <p>Search engines use the canonical tag to identify how to index a page.  i.e domain.com/page/ and domain.com/page/index.html are 2 different URLs that represent the same page. This could trigger a duplicate content penalty.  The canonical tag tells the search engine that they are same page and they should be indexed as domain.com/page/</p>    
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Parse CSS files', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php $tpl->displayCheckbox( $this, 'parse_css', 'Parse CSS files' ); ?>

    <p>This will result in better exports, but will consume more memory on the server. Try disabling this if you're unable to complete your export and suspect it's running out of memory.</p>
  </div>
</section>


<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Modify HTML', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php $tpl->displayCheckbox( $this, 'createEmptyFavicon', 'Insert empty icon rel to prevent favicon requests', 'checked' ); ?>

    <p>If you don't have a favicon for your site, block extra requests taking up speed</p>

    <?php $tpl->displayCheckbox( $this, 'forceHTTPS', 'Force rewriting any http links to https', 'checked' ); ?>

    <p>If you are left with a few remaining http protocol links in your exported site and are unable to fix in the original WordPress site, this option will force rewrite any links in the exported pages that start with http to https. Warning, this is a brute force approach and may alter texts on the page that should not be rewritten.</p>

    <?php $tpl->displayCheckbox( $this, 'forceRewriteSiteURLs', 'Force rewriting any left-over Site URLs to your Destination URL', 'checked' ); ?>

    <p>This is a last-resort method to rewrite any Site URLs that weren't able to be intelligently rewritten. This can be the case when the Site URL is within a custom HTML tag that WP2Static doesn't know how to handle, or within some inline CSS or JavaScript sections, for example.</p>
  </div>
</section>


</div><!-- end processing_setings -->
