<div class="add_ons" style="display:none;">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <img src="<?php echo plugins_url( '/../assets/aircraft.svg', __FILE__ ); ?>" style="max-width:250px" alt="Add-on">
  </div>

  <div class="content">
    <p>Whilst you can get a fully functioning static site using the free version of WP2Static, our suite of <a href="https://wp2static.com" target="_blank">Add-ons</a> give you a range of powerful enhancements. Browse our premium and free Add-ons for faster and more powerful static site exports.</p>
    <a href="https://wp2static.com" class="wp2static-btn btn-sm" target="_blank">Browse Add-on</a>
  </div>
</section>

<section class="wp2static-content-addon">

<?php

$add_ons = array(
    'wp2static-addon-ftp/wp2static-addon-ftp.php' => 'FTP',
    'wp2static-addon-s3/wp2static-addon-s3.php' => 'S3',
    'wp2static-addon-azure/wp2static-addon-azure.php' => 'Azure',
    'wp2static-addon-bitbucket/wp2static-addon-bitbucket.php' => 'Bitbucket',
    'wp2static-addon-bunnycdn/wp2static-addon-bunnycdn.php' => 'BunnyCDN',
    'wp2static-addon-github/wp2static-addon-github.php' => 'GitHub',
    'wp2static-addon-gitlab/wp2static-addon-gitlab.php' => 'GitLab',
    'wp2static-addon-netlify/wp2static-addon-netlify.php' => 'Netlify',
    'wp2static-addon-now/wp2static-addon-now.php' => 'now',
);

?>

<?php foreach ( $add_ons as $add_on_code => $add_on_name ) :

    if ( is_plugin_active( $add_on_code ) ) : ?>

  <div class="content addon wp2static-column active">
    <div class="main">
      <img src="<?php echo strtolower( plugins_url( "/../assets/$add_on_name.svg", __FILE__ ) ); ?>" alt="<?php echo $add_on_name; ?>" class="hero">
      <h2><?php echo $add_on_name; ?> Add-on</h2>
      <p>Deploy your site statically with <?php echo $add_on_name; ?> Add-on and feels the speed!</p>
    </div>
    <div class="bottom">
      <div class="left">
        <a href="#" id="GoToDeployTabButton" class="wp2static-btn blue" target="_blank">
          <i class="dashicon dashicon-yes"></i> Settings
        </a>
      </div>
      <div class="right"><a href="https://github.com/wp2static/wp2static-addon-<?php echo strtolower( $add_on_name ); ?>" target="_blank"><img src="<?php echo strtolower( plugins_url( '/../assets/github.svg', __FILE__ ) ); ?>" alt="GitHub" style="max-width:32px"></a></div>
    </div>
  </div>

    <?php else : ?>

  <div class="content addon wp2static-column noactive">
    <div class="main">
      <img src="<?php echo strtolower( plugins_url( "/../assets/$add_on_name.svg", __FILE__ ) ); ?>" alt="<?php echo $add_on_name; ?>" class="hero">
      <h2><?php echo $add_on_name; ?> Add-on</h2>
      <p>Deploy your site statically with <?php echo $add_on_name; ?> Add-on and feels the speed!</p>
    </div>
    <div class="bottom">
      <div class="left">
        <a href="https://wp2static.com/#download-purchase-donate" class="wp2static-btn">
          Get Add-on
        </a>
      </div>
      <div class="right"><a href="https://github.com/wp2static/wp2static-addon-<?php echo strtolower( $add_on_name ); ?>" target="_blank"><img src="<?php echo strtolower( plugins_url( '/../assets/github.svg', __FILE__ ) ); ?>" alt="GitHub" style="max-width:32px"></a></div>
    </div>
  </div>

    <?php endif; ?>
 
<?php endforeach; ?>

</section>

</div> <!-- end add-ons tab -->
