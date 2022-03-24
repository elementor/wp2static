<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded
// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * @var mixed[] $view
 */

use WP2Static\OptionRenderer;

$options = $view['coreOptions'];

$row = function( $name ) use ( $options ) {
    $opt = (array) $options[ $name ];
    return '<tr><td style="width: 50%">' . OptionRenderer::optionLabel( $opt, true ) .
            '</td><td>' . optionrenderer::optionInput( $opt ) . '</td></tr>';
}

?>

<div class="wrap">
    <form
        name="wp2static-ui-advanced-options"
        method="POST"
        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

    <h1>Advanced Options<h1>

    <h2>Detection Options</h2>

    <table class="widefat striped">
        <tbody>
            <?php echo $row( 'filenamesToIgnore' ); ?>
            <?php echo $row( 'fileExtensionsToIgnore' ); ?>
        </tbody>
    </table>

    <p/>

    <h2>Post-processing Options</h2>

    <table class="widefat striped">
        <tbody>
            <?php echo $row( 'crawlConcurrency' ); ?>
            <?php echo $row( 'skipURLRewrite' ); ?>
            <?php echo $row( 'hostsToRewrite' ); ?>
        </tbody>
    </table>

    <p/>

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_ui_save_advanced_options" />

    <button class="button btn-primary" type="submit">Save options</button>

    </form>
</div>
