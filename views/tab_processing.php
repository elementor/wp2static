<div id="processing_settings" v-show="currentTab == 'processing_settings'">

<section-with-checkbox
    id="useDocumentRelativeURLs"
    :title="fieldData.useDocumentRelativeURLs.title"
    :description="fieldData.useDocumentRelativeURLs.description"
    :hint="fieldData.useDocumentRelativeURLs.hint"
    :checked="useDocumentRelativeURLs"
></section-with-checkbox>

<section-with-checkbox
    id="useSiteRootRelativeURLs"
    :title="fieldData.useSiteRootRelativeURLs.title"
    :description="fieldData.useSiteRootRelativeURLs.description"
    :hint="fieldData.useSiteRootRelativeURLs.hint"
    :checked="useSiteRootRelativeURLs"
></section-with-checkbox>

<section-with-checkbox
    id="allowOfflineUsage"
    :title="fieldData.allowOfflineUsage.title"
    :description="fieldData.allowOfflineUsage.description"
    :hint="fieldData.allowOfflineUsage.hint"
    :checked="allowOfflineUsage"
></section-with-checkbox>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Base HREF', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php $tpl->displayTextfield( $this, 'baseHREF', 'Base HREF', '', '' ); ?>

    <p>Setting this will tell the browser to resolve all URLs using the URL you set (<code>https://mydomain.com</code>, <code>/</code>, etc).</p>

    <p><b>Note:</b>NEEDS CLARIFICATION - WHAT DO THE TESTS SHOW ? If you want to deploy your static site to work in a subdirectory of any domain, set your Destination URL to the same as your Site URL and choose Use Document Relative URLs.</p>

    <p><b>Note:</b>This setting is ignored if "Alow offline usage" setting is on.</p>
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

    <textarea class="wp2static-textarea" name="rewriteRules" id="rewriteRules" rows="5" cols="10"><?php echo $this->options->rewriteRules ? $this->options->rewriteRules : ''; ?></textarea>
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

    <textarea class="widefat" name="renameRules" id="renameRules" rows="5" cols="10"><?php echo $this->options->renameRules ? $this->options->renameRules : ''; ?></textarea>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Remove cruft', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">

    <field-set-with-checkbox
        id="removeConditionalHeadComments"
        :description="fieldData.removeConditionalHeadComments.description"
        :hint="fieldData.removeConditionalHeadComments.hint"
        :checked="removeConditionalHeadComments"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="removeWPMeta"
        :description="fieldData.removeWPMeta.description"
        :hint="fieldData.removeWPMeta.hint"
        :checked="removeWPMeta"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="removeWPLinks"
        :description="fieldData.removeWPLinks.description"
        :hint="fieldData.removeWPLinks.hint"
        :checked="removeWPLinks"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="removeHTMLComments"
        :description="fieldData.removeHTMLComments.description"
        :hint="fieldData.removeHTMLComments.hint"
        :checked="removeHTMLComments"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="removeCanonical"
        :description="fieldData.removeCanonical.description"
        :hint="fieldData.removeCanonical.hint"
        :checked="removeCanonical"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="removeRobotsNoIndex"
        :description="fieldData.removeRobotsNoIndex.description"
        :hint="fieldData.removeRobotsNoIndex.hint"
        :checked="removeRobotsNoIndex"
    ></field-set-with-checkbox>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Parse CSS files', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">

    <field-set-with-checkbox
        id="parseCSS"
        :description="fieldData.parseCSS.description"
        :hint="fieldData.parseCSS.hint"
        :checked="parseCSS"
    ></field-set-with-checkbox>

  </div>
</section>


<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Modify HTML', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">

    <field-set-with-checkbox
        id="createEmptyFavicon"
        :description="fieldData.createEmptyFavicon.description"
        :hint="fieldData.createEmptyFavicon.hint"
        :checked="createEmptyFavicon"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="forceHTTPS"
        :description="fieldData.forceHTTPS.description"
        :hint="fieldData.forceHTTPS.hint"
        :checked="forceHTTPS"
    ></field-set-with-checkbox>

    <field-set-with-checkbox
        id="forceRewriteSiteURLs"
        :description="fieldData.forceRewriteSiteURLs.description"
        :hint="fieldData.forceRewriteSiteURLs.hint"
        :checked="forceRewriteSiteURLs"
    ></field-set-with-checkbox>

  </div>
</section>


</div><!-- end processing_setings -->
