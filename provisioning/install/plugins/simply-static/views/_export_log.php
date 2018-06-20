<?php
namespace Simply_Static;

if ( is_array( $this->static_pages ) && count( $this->static_pages ) ) : ?>

	<?php $num_errors = count( array_filter( $this->static_pages, function($p) { return $p->error_message != false; } ) ); ?>

	<div class='tablenav top'>
		<?php include '_pagination.php'; ?>
	</div>

	<table class='widefat striped'>
		<thead>
			<tr>
				<th><?php _e( 'Code', 'simply-static' ); ?></th>
				<th><?php _e( 'URL', 'simply-static' ); ?></th>
				<th><?php _e( 'Notes', 'simply-static' ); ?></th>
				<?php if ( $num_errors > 0 ) : ?>
				<th><?php echo sprintf( __( "Errors (%d)", 'simply-static' ), $num_errors ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>

		<?php foreach ( $this->static_pages as $static_page ) : ?>
			<tr>
				<?php $processable = in_array( $static_page->http_status_code, Page::$processable_status_codes ); ?>
				<td class='status-code <?php if ( ! $processable ) { echo 'unprocessable'; } ?>'>
					<?php echo $static_page->http_status_code; ?>
				</td>
				<td class='url'><a href='<?php echo $static_page->url; ?>'><?php echo $static_page->url; ?></a></td>
				<td class='status-message'>
					<?php
						$msg = '';
						$parent_static_page = $static_page->parent_static_page();
						if ( $parent_static_page ) {
							$display_url = Util::get_path_from_local_url( $parent_static_page->url );
							$msg .= "<a href='" . $parent_static_page->url . "'>" .sprintf( __( 'Found on %s', 'simply-static' ), $display_url ). "</a>";
						}
						if ( $msg !== '' && $static_page->status_message ) {
							$msg .= '; ';
						}
						$msg .= $static_page->status_message;
						echo $msg;
					?>
				</td>
				<?php if ( $num_errors > 0 ) : ?>
				<td class='error-message'>
					<?php echo $static_page->error_message; ?>
				</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class='tablenav bottom'>
		<?php include '_pagination.php'; ?>
	</div>

<?php endif ?>
