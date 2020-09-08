<div class="wrap">
    <form
        name="wp2static-job-options"
        method="POST"
        action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <br>

    <table class="widefat striped">
        <thead>
            <tr>
                <td style="width:33%;">
                    Events to queue new jobs
                </td>
                <td>
                    &nbsp;
                </td>
                <td>
                    Enabled?
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width:33%;">
                    <label
                        for="<?php echo $view['jobOptions']['queueJobOnPostSave']->name; ?>"
                    /><b><?php echo $view['jobOptions']['queueJobOnPostSave']->label; ?></b></label>
                </td>
                <td>
                    <?php echo $view['jobOptions']['queueJobOnPostSave']->description; ?>
                </td>
                <td>
                    <input
                        type="checkbox"
                        id="<?php echo $view['jobOptions']['queueJobOnPostSave']->name; ?>"
                        name="<?php echo $view['jobOptions']['queueJobOnPostSave']->name; ?>"
                        value="1"
                        <?php echo (int) $view['jobOptions']['queueJobOnPostSave']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>
            </tr>
            <tr>
                <td style="width:33%;">
                    <label
                        for="<?php echo $view['jobOptions']['queueJobOnPostDelete']->name; ?>"
                    /><b><?php echo $view['jobOptions']['queueJobOnPostDelete']->label; ?></b></label>
                </td>
                <td>
                    <?php echo $view['jobOptions']['queueJobOnPostDelete']->description; ?>
                </td>
                <td>
                    <input
                        type="checkbox"
                        id="<?php echo $view['jobOptions']['queueJobOnPostDelete']->name; ?>"
                        name="<?php echo $view['jobOptions']['queueJobOnPostDelete']->name; ?>"
                        value="1"
                        <?php echo (int) $view['jobOptions']['queueJobOnPostDelete']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>
            </tr>
        </tbody>
    </table>


    <h4>Jobs that will be added to queue</h4>

    <table class="widefat striped">
        <thead>
            <tr>
                <td style="text-align:center;">
                    Detect URLs
                </td>
                <td style="text-align:center;">
                    Crawl Site
                </td>
                <td style="text-align:center;">
                    Post-process
                </td>
                <td style="text-align:center;">
                    Deploy
                </td>
            </tr>
        </thead>
        <tbody>
            <tr style="text-align:center;">
                <td>
                    <input
                        type="checkbox"
                        id="<?php echo $view['jobOptions']['autoJobQueueDetection']->name; ?>"
                        name="<?php echo $view['jobOptions']['autoJobQueueDetection']->name; ?>"
                        value="1"
                        <?php echo (int) $view['jobOptions']['autoJobQueueDetection']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>

                <td>
                    <input
                        type="checkbox"
                        id="<?php echo $view['jobOptions']['autoJobQueueCrawling']->name; ?>"
                        name="<?php echo $view['jobOptions']['autoJobQueueCrawling']->name; ?>"
                        value="1"
                        <?php echo (int) $view['jobOptions']['autoJobQueueCrawling']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>

                <td>
                    <input
                        type="checkbox"
                        id="<?php echo $view['jobOptions']['autoJobQueuePostProcessing']->name; ?>"
                        name="<?php echo $view['jobOptions']['autoJobQueuePostProcessing']->name; ?>"
                        value="1"
                        <?php echo (int) $view['jobOptions']['autoJobQueuePostProcessing']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>

                <td>
                    <input
                        type="checkbox"
                        id="<?php echo $view['jobOptions']['autoJobQueueDeployment']->name; ?>"
                        name="<?php echo $view['jobOptions']['autoJobQueueDeployment']->name; ?>"
                        value="1"
                        <?php echo (int) $view['jobOptions']['autoJobQueueDeployment']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>
            </tr>
        </tbody>
    </table>

    <br>

    <b>Process Queue Interval</b>

    <br>

    <label
        for=""
    />WP-Cron will attempt to process the Job Queue at this interval:</label>

    <select
        id="<?php echo $view['jobOptions']['processQueueInterval']->name; ?>"
        name="<?php echo $view['jobOptions']['processQueueInterval']->name; ?>"
        value="<?php echo (int) $view['jobOptions']['processQueueInterval']->value; ?>"
    />
        <option
            <?php echo (int) $view['jobOptions']['processQueueInterval']->value === 0 ? 'selected' : ''; ?>
            value="0">disable (never)</option>
        <option
            <?php echo (int) $view['jobOptions']['processQueueInterval']->value === 1 ? 'selected' : ''; ?>
            value="1">every minute</option>
        <option
            <?php echo (int) $view['jobOptions']['processQueueInterval']->value === 5 ? 'selected' : ''; ?>
            value="5">every 5 minutes</option>
        <option
            <?php echo (int) $view['jobOptions']['processQueueInterval']->value === 10 ? 'selected' : ''; ?>
            value="10">every 10 minutes</option>
    </select>

    <p><i>If WP-Cron is not expected to be triggered by site visitors, you can also call `wp-cron.php` directly, run the WP-CLI command `wp wp2static process_queue` or call the hook `wp2staticProcessQueue` from within your own theme or plugin.</i></p>

        <button class="button btn-primary">Save Job Automation Settings</button>
        <?php wp_nonce_field( $view['nonce_action'] ); ?>
        <input name="action" type="hidden" value="wp2static_ui_save_job_options" />
    </form>

    <br>
    <form
        name="wp2static-manually-enqueue-jobs"
        method="POST"
        action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

        <?php wp_nonce_field( 'wp2static-manually-enqueue-jobs' ); ?>
        <input name="action" type="hidden" value="wp2static_manually_enqueue_jobs" />

        <button class="button">Manually Enqueue Jobs Now</button>
    </form>

    <hr>

    <h3>Job Queue/History</h3>

    <p><i><a href="<?php echo admin_url('admin.php?page=wp2static-jobs'); ?>">Refresh page</a> to see latest status</i><p>

    <hr>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Job</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($view['jobs'] as $job): ?>
            <tr>
                <td><?php echo $job->created_at; ?></td>
                <td><?php echo $job->job_type; ?></td>
                <td><?php echo $job->status; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>

    <form
        name="wp2static-delete-jobs-queue"
        method="POST"
        action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_delete_jobs_queue" />

    <button class="wp2static-button button btn-danger">Delete all Jobs from Queue</button>

    </form>

    <!-- TODO: consider manual queue processing, needs further testing, unstable execution so far

    <br>

    <form
        name="wp2static-process-jobs-queue"
        method="POST"
        action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_process_jobs_queue" />

    <button class="wp2static-button button btn-danger">Manually process Job Queue</button>

    </form>

-->
</div>
