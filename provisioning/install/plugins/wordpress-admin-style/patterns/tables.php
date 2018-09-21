<h2><?php esc_attr_e( 'Tables', 'WpAdminStyle' ); ?></h2>

<p><strong>Table with class <code>form-table</code></strong></p>
<table class="form-table">
	<tr>
		<th class="row-title"><?php esc_attr_e( 'Table header cell #1', 'WpAdminStyle' ); ?></th>
		<th><?php esc_attr_e( 'Table header cell #2', 'WpAdminStyle' ); ?></th>
	</tr>
	<tr valign="top">
		<td scope="row"><label for="tablecell"><?php esc_attr_e(
					'Table data cell #1, with label', 'WpAdminStyle'
				); ?></label></td>
		<td><?php esc_attr_e( 'Table Cell #2', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr valign="top" class="alternate">
		<td scope="row"><label for="tablecell"><?php esc_attr_e(
					'Table Cell #3, with label and class', 'WpAdminStyle'
				); ?> <code>alternate</code></label></td>
		<td><?php esc_attr_e( 'Table Cell #4', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr valign="top">
		<td scope="row"><label for="tablecell"><?php esc_attr_e(
					'Table Cell #5, with label', 'WpAdminStyle'
				); ?></label></td>
		<td><?php esc_attr_e( 'Table Cell #6', 'WpAdminStyle' ); ?></td>
	</tr>
</table>

<br class="clear" />

<p><strong>Table with class <code>widefat</code></strong></p>
<table class="widefat">
	<tr>
		<th class="row-title"><?php esc_attr_e( 'Table header cell #1', 'WpAdminStyle' ); ?></th>
		<th><?php esc_attr_e( 'Table header cell #2', 'WpAdminStyle' ); ?></th>
	</tr>
	<tr>
		<td class="row-title"><label for="tablecell"><?php esc_attr_e(
					'Table Cell #1, with label', 'WpAdminStyle'
				); ?></label></td>
		<td><?php esc_attr_e( 'Table Cell #2', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr class="alternate">
		<td class="row-title"><label for="tablecell"><?php esc_attr_e(
					'Table Cell #3, with label and class', 'WpAdminStyle'
				); ?> <code>alternate</code></label></td>
		<td><?php esc_attr_e( 'Table Cell #4', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr>
		<td class="row-title"><?php esc_attr_e( 'Table Cell #5, without label', 'WpAdminStyle' ); ?></td>
		<td><?php esc_attr_e( 'Table Cell #6', 'WpAdminStyle' ); ?></td>
	</tr>
</table>

<br class="clear" />
<p><strong>Table with class <code>widefat</code></strong></p>
<table class="widefat">
	<thead>
	<tr>
		<th class="row-title"><?php esc_attr_e( 'Table header cell #1', 'WpAdminStyle' ); ?></th>
		<th><?php esc_attr_e( 'Table header cell #2', 'WpAdminStyle' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td class="row-title"><label for="tablecell"><?php esc_attr_e(
					'Table Cell #1, with label', 'WpAdminStyle'
				); ?></label></td>
		<td><?php esc_attr_e( 'Table Cell #2', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr class="alternate">
		<td class="row-title"><label for="tablecell"><?php esc_attr_e(
					'Table Cell #3, with label and class', 'WpAdminStyle'
				); ?> <code>alternate</code></label></td>
		<td><?php esc_attr_e( 'Table Cell #4', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr>
		<td class="row-title"><?php esc_attr_e( 'Table Cell #5, without label', 'WpAdminStyle' ); ?></td>
		<td><?php esc_attr_e( 'Table Cell #6', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr class="alt">
		<td class="row-title"><?php esc_attr_e(
				'Table Cell #7, without label and with class', 'WpAdminStyle'
			); ?> <code>alt</code></td>
		<td><?php esc_attr_e( 'Table Cell #8', 'WpAdminStyle' ); ?></td>
	</tr>
	<tr class="form-invalid">
		<td class="row-title"><?php esc_attr_e(
				'Table Cell #9, without label and with class', 'WpAdminStyle'
			); ?> <code>form-invalid</code></td>
		<td><?php esc_attr_e( 'Table Cell #10', 'WpAdminStyle' ); ?></td>
	</tr>
	</tbody>
	<tfoot>
	<tr>
		<th class="row-title"><?php esc_attr_e( 'Table header cell #1', 'WpAdminStyle' ); ?></th>
		<th><?php esc_attr_e( 'Table header cell #2', 'WpAdminStyle' ); ?></th>
	</tr>
	</tfoot>
</table>
