<h2><?php esc_attr_e( 'Form Elements: Fieldset and Input Field', 'WpAdminStyle' ); ?></h2>

<fieldset>
	<legend class="screen-reader-text"><span>Fieldset Example</span></legend>
	<label for="users_can_register">
		<input name="" type="checkbox" id="users_can_register" value="1" />
		<span><?php esc_attr_e( 'Checkbox description with legend class .screen-reader-text', 'WpAdminStyle' ); ?></span>
	</label>
</fieldset>

<fieldset>
	<legend class="screen-reader-text"><span>input type="radio"</span></legend>
	<label title='g:i a'>
		<input type="radio" name="example" value="" />
		<span><?php esc_attr_e( 'Radio description with legend class .screen-reader-text', 'WpAdminStyle' ); ?></span>
	</label><br>
	<label title='g:i a'>
		<input type="radio" name="example" value="" />
		<span><?php esc_attr_e( 'Radio description #2 with legend class .screen-reader-text', 'WpAdminStyle' ); ?></span>
	</label>
</fieldset>
