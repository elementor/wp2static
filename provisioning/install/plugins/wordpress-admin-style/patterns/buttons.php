<h2><?php esc_attr_e( 'Buttons', 'WpAdminStyle' ); ?></h2>
<p><?php esc_attr_e( 'Use core function to create buttons:' ); ?> <code>submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null )</code></p>

<br>
<input class="button-primary" type="submit" name="Example" value="<?php esc_attr_e( 'Example Primary Button' ); ?>" />

<br>
<?php submit_button(
	'Example', $type = 'small', $name = 'submit', $wrap = FALSE, $other_attributes = NULL
); ?>

<br>
<?php submit_button(
	'Example', $type = 'delete', $name = 'submit', $wrap = TRUE, $other_attributes = NULL
); ?>

<br>
<input class="button-secondary" type="submit" value="<?php esc_attr_e( 'Example Secondary Button' ); ?>" />

<br>
<a class="button-secondary" href="#" title="<?php esc_attr_e( 'Title for Example Link Button' ); ?>"><?php esc_attr_e( 'Example Link Button' ); ?></a>
