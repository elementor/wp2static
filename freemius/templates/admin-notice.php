<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$dismiss_text = fs_text_x_inline( 'Dismiss', 'as close a window', 'dismiss' );
?>
<div<?php if ( ! empty( $VARS['id'] ) ) : ?> data-id="<?php echo $VARS['id'] ?>"<?php endif ?><?php if ( ! empty( $VARS['manager_id'] ) ) : ?> data-manager-id="<?php echo $VARS['manager_id'] ?>"<?php endif ?>
	class="<?php
		switch ( $VARS['type'] ) {
			case 'error':
				echo 'error form-invalid';
				break;
			case 'promotion':
				echo 'updated promotion';
				break;
			case 'update':
//			echo 'update-nag update';
//			break;
			case 'success':
			default:
				echo 'updated success';
				break;
		}
	?> fs-notice<?php if ( ! empty( $VARS['sticky'] ) ) {
		echo ' fs-sticky';
	} ?><?php if ( ! empty( $VARS['plugin'] ) ) {
		echo ' fs-has-title';
	} ?>"><?php if ( ! empty( $VARS['plugin'] ) ) : ?>
		<label class="fs-plugin-title"><?php echo $VARS['plugin'] ?></label>
	<?php endif ?>
	<?php if ( ! empty( $VARS['sticky'] ) ) : ?>
		<div class="fs-close"><i class="dashicons dashicons-no"
		                         title="<?php echo esc_attr( $dismiss_text ) ?>"></i> <span><?php echo esc_html( $dismiss_text ) ?></span>
		</div>
	<?php endif ?>
	<div class="fs-notice-body">
		<?php if ( ! empty( $VARS['title'] ) ) : ?><b><?php echo $VARS['title'] ?></b> <?php endif ?>
		<?php echo $VARS['message'] ?>
	</div>
</div>
