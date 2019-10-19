<div id="workflow_tab" v-show="currentTab == 'workflow_tab'">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/dev-server.svg', __FILE__ ); ?>" style="max-width:250px;min-height:200px;" alt="Add-on">

    <h2>Development</h2>

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
       <li><b>Local DNS resolution</b></li>
       <li><b>PHP max_execution_time</b>
         <span :style="siteInfo.maxExecutionTime == 0 ? 'color:#3ad23a;': 'color:red;'">
            {{ siteInfo.maxExecutionTime }} {{ siteInfo.maxExecutionTime == 0 ? '(Unlimited)': 'secs' }}
         </span>
        </li>
       <li><b>Writable uploads dir</b> <span v-if="siteInfo.uploadsWritable" class="dashicons dashicons-yes" style="color: #3ad23a;"></span></li>
    </ul>

  </div>
  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/staging-server.svg', __FILE__ ); ?>" style="max-width:250px;min-height:200px;" alt="Add-on">

    <h2>Staging</h2>

    <p>Automatically deploy any changes to your WordPress site here. If you don't want to stage before production use your production environment details here.</p>

    <h3>Deployment summary</h3>
    <ul>
       <li id="deploymentMethodStaging"><b>Deployment Method</b> {{ currentDeploymentMethod }}</li>
       <li><b>Destination URL</b> <a :href="baseUrl" target="_blank">{{ baseUrl }}</a></li>
    </ul>
  </div>

  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/production-server.svg', __FILE__ ); ?>" style="max-width:250px;min-height:200px;" alt="Add-on">

    <h2>Production</h2>

    <p>For those who want to preview site changes on staging before going live, enter production deployment details here. Production deploys use the same generated static site content as staging, so choose a URL processing scheme that will work on either domain.</p>

    <h3>Deployment summary</h3>
    <ul>
       <li><b>Deployment Method</b> {{ currentDeploymentMethodProduction }}</li>
       <li><b>Destination URL</b> <a :href="baseUrlProduction" target="_blank">{{ baseUrlProduction }}</a></li>
    </ul>
  </div>
</section>


</div> <!-- end workflow settings -->
