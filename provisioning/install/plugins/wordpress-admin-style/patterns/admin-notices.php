<h2><?php esc_attr_e( 'Admin Notices', 'WpAdminStyle' ); ?></h2>

<p>
	<?php
	printf(
		// translators: Leave always a hint for translators to understand the placeholders.
		__( 'Define type via parameter (same as CSS classes) with <a href="%s" target="_blank">function add_settings_error()</a>, or use class(es) on a wrapping <code>div</code>.',
		'WpAdminStyle' ), 'https://developer.wordpress.org/reference/functions/add_settings_error/'
	);
	?>
</p>
<p>
	<?php
	printf(
		// translators: Leave always a hint for translators to understand the placeholders.
		__( 'Since WordPress version 4.2 there are more classes and paths available. See <a href="%s" target="_blank">this post on make.w.org</a> for further details.', 'WpAdminStyle' ),
		'https://make.wordpress.org/core/2015/04/23/spinners-and-dismissible-admin-notices-in-4-2/'
	);
	?>
</p>
<p>
	<?php
	_e( 'Note: The <code>inline</code> class is only to leave the notices here. On default WordPress will hide them via javascript.', 'WpAdminStyle' );
	?>
</p>

<div class="notice notice-error inline">
	<p>
		<?php
		printf(
			// translators: Leave always a hint for translators to understand the placeholders.
			esc_attr__( 'class %1$s with paragraph and %2$s class', 'WpAdminStyle' ),
			'<code>.notice-error</code>',
			'<code>.inline</code>'
		);
		?>
	</p>
</div>

<div class="notice notice-warning inline">
	<p>
		<?php
		printf(
			// translators: Leave always a hint for translators to understand the placeholders.
			esc_html__( 'class %1$s with paragraph and %2$s class', 'WpAdminStyle' ),
			'<code>.notice-warning</code>',
			'<code>.inline</code>'
		);
		?>
	</p>
</div>

<div class="notice notice-success inline">
	<p>
		<?php
		printf(
			// translators: Leave always a hint for translators to understand the placeholders.
			esc_html__( 'class %1$s with paragraph and %2$s class', 'WpAdminStyle' ),
			'<code>.notice-success</code>',
			'<code>.inline</code>'
		);
		?>
	</p>
</div>

<div class="notice notice-info is-dismissible inline">
	<p>
		<?php
		printf(
			// translators: Leave always a hint for translators to understand the placeholders.
			esc_attr__( 'class %1$s with paragraph include %2$s  and %3$s class', 'WpAdminStyle' ),
			'<code>.notice-info</code>',
			'<code>.is-dismissible</code>',
			'<code>.inline</code>'
		);
		?>
	</p>
</div>

<div class="notice notice-info inline">
	<p>
		<?php
		printf(
			// translators: %1$s is a code fragment for the notice information and %2$s is the inline class code.
			esc_attr__( 'class %1$s with paragraph and %2$s class', 'WpAdminStyle' ),
			'<code>.notice-info</code>',
			'<code>.inline</code>'
		);
		?>
	</p>
</div>
