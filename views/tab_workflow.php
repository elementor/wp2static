<div id="workflow_tab" v-show="currentTab == 'workflow_tab'">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <h2>WP2Static</h2>

    <p>Run WP2Static on your local computer or private web server. It's WordPress as usual, but without the security concerns. WP2Static generates a static HTML copy of this site, ready for deployment to super-fast static hosting.</p>

    <h3>Health Checks</h3>
    <ul>
       <li>
            <b>Non-public dev server</b>
            <span
                v-if="publiclyAccessible == ''"
                class="dashicons dashicons-clock"
                style="color: #FE8F25;"
            ></span>
         <span
            v-if="publiclyAccessible == ''"
            :style="publiclyAccessible == '' ? 'color:#FE8F25;': 'color:red;'">
            {{ publiclyAccessible == '' ? 'Checking' : '' }}
         </span>
         <span
             v-if="publiclyAccessible == 'Private'"
             class="dashicons dashicons-yes"
             style="color: #3ad23a;"
         >
         </span>
         <span
             v-if="publiclyAccessible == 'Public'"
             class="dashicons dashicons-no"
             style="color: red;"
         >
         </span>
         <span
             v-if="publiclyAccessible == 'Unknown'"
             class="dashicons dashicons-warning"
             style="color: gray;"
         >
         </span>
         <span
            v-if="publiclyAccessible == 'Private' || publiclyAccessible == 'Public'"
            :style="publiclyAccessible == 'Private' ? 'color:#3ad23a;': 'color:red;'">
            {{ publiclyAccessible == 'Private' ? 'Private' : 'Public' }}
         </span>
         <span
            v-if="publiclyAccessible == 'Unknown'"
            style="color:gray;'">
            Unknown
         </span>
        </li>
       <li><b>Local DNS resolution</b>
            <span
                v-if="dnsResolution == ''"
                class="dashicons dashicons-clock"
                style="color: #FE8F25;"
            ></span>
         <span
            v-if="dnsResolution == ''"
            :style="dnsResolution == '' ? 'color:#FE8F25;': 'color:red;'">
            {{ dnsResolution == '' ? 'Checking' : '' }}
         </span>
         <span
             v-if="dnsResolution == 'No'"
             class="dashicons dashicons-no"
             style="color: red;"
         >
         </span>
         <span
            v-if="dnsResolution == 'Yes' || dnsResolution == 'No'"
            :style="dnsResolution == 'Yes' ? 'color:#3ad23a;': 'color:red;'">
            {{ dnsResolution }}
         </span>
         <span
            v-if="dnsResolution == 'Unknown' || dnsResolution == 'no shell_exec'"
             class="dashicons dashicons-warning"
             style="color: gray;"
         >
         </span>
         <span
            v-if="dnsResolution == 'Unknown' || dnsResolution == 'no shell_exec'"
            style="color:gray;">
            {{ dnsResolution }}
         </span>
       </li>
       <li><b>PHP max_execution_time</b>
         <span :style="siteInfo.maxExecutionTime == 0 ? 'color:#3ad23a;': 'color:red;'">
            {{ siteInfo.maxExecutionTime }} {{ siteInfo.maxExecutionTime == 0 ? '(Unlimited)': 'secs' }}
         </span>
        </li>
       <li><b>Writable uploads dir</b> <span v-if="siteInfo.uploadsWritable" class="dashicons dashicons-yes" style="color: #3ad23a;"></span></li>
    </ul>

  </div>

  <div class="content" style="max-width:33%">
    <div id="progress-container">
      <div id="progress">
        <div v-if="progress" id="pulsate-css"></div>
        <div id="current_action">
            {{ currentAction }}
        </div>
      </div>

      <p id="exportDuration" style="display:block;"></p>
    </div>

  <button v-if="progress" v-on:click="cancelExport" class="wp2static-btn orange" id="wp2staticCancelButton">
    <?php echo __( 'Cancel Export', 'static-html-output-plugin' ); ?>
  </button>

  <!-- TODO: set action to grab ZIP download URL from button vs anchor -->
  <a :href="zipURL" target="_blank">
  <button
    id="downloadZIP"
    v-if="progress == false && currentDeploymentMethod == 'zip' && workflowStatus == 'deploySuccess'"
    class="wp2static-btn btn-call-to-action"
    >
    <?php echo __( 'Download ZIP', 'static-html-output-plugin' ); ?>
  </button>
  </a>

  <a href="#" class="wp2static-btn btn-call-to-action" target="_blank" id="goToMyStaticSite" style="display:none;">
    <?php echo __( 'Open Deployed Site', 'static-html-output-plugin' ); ?>
  </a>

  <div id="export_timer"></div>

    <p>1-click detect/crawl/deploy button goes here</p>

    <p>automation and caching options here?</p>

  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <h2>URL Detection</h2>

    <p>WP2Static detects resources in a WordPress site by querying its database, installed plugins, themes and the filesystem for known URLs.</p>


  </div>
  <div class="content" style="max-width:33%">
    <h2>Crawl</h2>

    <p>Detect and crawl all HTML, JS, CSS, images, etc and save into a self-contained static website.</p>

  </div>

  <div class="content" style="max-width:33%">
    <h2>Deploy</h2>

    <p>Deploy your generated static site to remote servers, git repositories or create a compressed archive to suit your release process.</p>

  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <ul>
       <li><b>Detected URLs</b>
            {{ detectedURLsCount }}
        </li>
    </ul>

  </div>

  <div class="content" style="max-width:33%">
    <ul>
       <li><b>Crawl URL</b> <a :href="baseUrl" target="_blank">{{ siteInfo.site_url }}</a></li>
    </ul>

  </div>

  <div class="content" style="max-width:33%">
    <ul>
       <li><b>Deployment method</b> {{ currentDeploymentMethod }}</li>
       <li><b>Destination URL</b> <a :href="baseUrl" target="_blank">{{ baseUrl }}</a></li>
    </ul>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <button
      :disabled="progress"
      v-on:click="detectURLs"
      class="wp2static-btn blue"
      id="wp2staticDetectURLsButton">
      <?php echo __( 'Detect URLs', 'static-html-output-plugin' ); ?>
    </button>
  </div>

  <div class="content" style="max-width:33%">
    <button
      :disabled="progress"
      v-on:click="generateStaticSite"
      class="wp2static-btn blue"
      id="wp2staticGenerateButton">
      <?php echo __( 'Crawl site', 'static-html-output-plugin' ); ?>
    </button>
  </div>

  <div class="content" style="max-width:33%">
    <button
        :disabled="progress"
        v-on:click="startExport"
        class="wp2static-btn blue">
      <?php echo __( 'Deploy', 'static-html-output-plugin' ); ?>
    </button>
  </div>
</div> <!-- end workflow settings -->
