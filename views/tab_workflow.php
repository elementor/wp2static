<div class="workflow_tab">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:33%">
    <img src="<?php echo plugins_url( '/../assets/dev-server.svg', __FILE__ ); ?>" style="max-width:250px" alt="Add-on">

    <h2>Development</h2>

    <p>Run WP2Static on your local computer or private web server. It's WordPress as usual, but without the security concerns. WP2Static generates a static HTML copy of this site, ready for deployment to super-fast static hosting.</p>

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
        <span><nest1><nest2>sdfsdfsdf</nest2></nest1></span>
    </ul>
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
  </div>
</section>


</div> <!-- end workflow settings -->
