<div class="workflow_tab">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/dev-server.svg', __FILE__ ); ?>" style="max-width:250px" alt="Add-on">

    <h2>Development</h2>

    <p>This server is where you run WordPress as usual. Do not run this on your production server!</p>

    <h3>Health Checks</h3>
    <ul>
       <li>Publicly accessible</li>
       <li>Local DNS resolution</li>
       <li>PHP max_execution_time</li>
       <li>Writable uploads dir</li>
    </ul>

  </div>
  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/staging-server.svg', __FILE__ ); ?>" style="max-width:250px" alt="Add-on">

    <h2>Staging</h2>

    <p>QA your static site before going to production.</p>

    <h3>Deployment summary</h3>
    <ul>
       <li><b>Deployment Method</b> Netlify</li>
       <li><b>Destination URL</b> https://testmysite.netlify.com</li>
    </ul>

    <button>Deploy to Staging</button>

  </div>

  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/production-server.svg', __FILE__ ); ?>" style="max-width:250px" alt="Add-on">

    <h2>Production</h2>

    <p>Your live site hosting.</p>

    <h3>Deployment summary</h3>
    <ul>
       <li><b>Deployment Method</b> S3</li>
       <li><b>Destination URL</b> https://www.mywebsite.com</li>
    </ul>

    <button>Deploy to Production</button>
  </div>
</section>


</div> <!-- end workflow settings -->
