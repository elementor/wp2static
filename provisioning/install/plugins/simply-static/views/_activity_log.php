<?php
namespace Simply_Static;

foreach ( $this->status_messages as $state_name => $status ) : ?>
	<div class='activity <?php echo $state_name . '-state'; ?>'>[<?php echo $status['datetime']; ?>] <?php echo $status['message']; ?></div>
<?php endforeach; ?>
