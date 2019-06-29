<div id="automation_settings" v-show="currentTab == 'automation_settings'">

    <section-with-checkbox
        :id="fieldData.redeployOnPostUpdates.id"
        :title="fieldData.redeployOnPostUpdates.title"
        :description="fieldData.redeployOnPostUpdates.description"
        :hint="fieldData.redeployOnPostUpdates.hint"
        :checked="options.redeployOnPostUpdates">
    </section-with-checkbox>

    <section-with-checkbox
        :id="fieldData.completionEmail.id"
        :title="fieldData.completionEmail.title"
        :description="fieldData.completionEmail.description"
        :hint="fieldData.completionEmail.hint"
        :checked="options.completionEmail">
    </section-with-checkbox>

    <section class="wp2static-content wp2static-flex">
      <div class="content" style="max-width:30%">
        <h2><?php echo __( 'Schedule deploys with WP-Cron', 'static-html-output-plugin' ); ?></h2>
      </div>

      <div class="content">
        <p>Use the <a href="" target="_blank">WP-Crontrol plugin</a> and WP2Static's hook named <code>wp_static_html_output_server_side_export_hook</code> to schedule automated static site generation and deployment to staging.</p>
       </div>
    </section>

</div>
