<?php $run_nonce = wp_create_nonce( "wp2static-run-page" ); ?>

<script type="text/javascript">
var latest_log_row = 0;

jQuery(document).ready(function($){
    var run_data = {
        action: 'wp2static_run',
        security: '<?php echo $run_nonce; ?>',
    };

    var log_data = {
        dataType: 'text',
        action: 'wp2static_poll_log',
        startRow: latest_log_row,
        security: '<?php echo $run_nonce; ?>',
    };

    $( "#wp2static-run" ).click(function() {
        $("#wp2static-spinner").addClass("is-active");

        $.post(ajaxurl, run_data, function(response) {
            $("#wp2static-spinner").removeClass("is-active");

        });

    });

    $( "#wp2static-poll-logs" ).click(function() {
        $.post(ajaxurl, log_data, function(response) {
            console.log(response);

            $('#wp2static-run-log').val(response);
        });

    });
});
</script>

<br>

<button id="wp2static-run">Generate static site</button>

<div id="wp2static-spinner" class="spinner" style="padding:2px;float:none;"></div>

<br>
<br>

<button id="wp2static-poll-logs">Refresh logs</button>
<br>
<br>
<textarea id="wp2static-run-log" rows=30 style="width:99%;">
Logs will appear here
</textarea>
