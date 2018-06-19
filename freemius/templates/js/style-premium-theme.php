<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.2.2.7
	 *
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array    $VARS
	 * @var Freemius $fs
	 */
	$fs = freemius( $VARS['id'] );

	$slug = $fs->get_slug();

?>
<script type="text/javascript">
	(function ($) {
		// Select the premium theme version.
		var $theme             = $('#<?php echo $slug ?>-premium-name').parents('.theme'),
		    addPremiumMetadata = function (firstCall) {
			    if (!firstCall) {
				    // Seems like the original theme element is removed from the DOM,
				    // so we need to reselect the updated one.
				    $theme = $('#<?php echo $slug ?>-premium-name').parents('.theme');
			    }

			    if (0 === $theme.find('.fs-premium-theme-badge').length) {
				    $theme.addClass('fs-premium');

				    $theme.append('<span class="fs-premium-theme-badge">' + <?php echo json_encode( $fs->get_text_inline( 'Premium', 'premium' ) ) ?> +'</span>');
			    }
		    };

		addPremiumMetadata(true);

		$theme.contentChange(addPremiumMetadata);
	})(jQuery);
</script>