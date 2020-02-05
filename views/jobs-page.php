<h3>Automate job creation</h3>

<p><i>On these events:</i></p>

<label
    for=""
/>Post Update</label>

<input
    type="checkbox"
    id="autoJobTriggerPostUpdate"
    name="autoJobTriggerPostUpdate"
    value="1"
/>

<label
    for=""
/>Post Update</label>

<input
    type="checkbox"
    id="autoJobTriggerPostUpdate"
    name="autoJobTriggerPostUpdate"
    value="1"
/>

<p><i>Schedule these types of jobs:</i></p>

<label
    for=""
/>Detection</label>

<input
    type="checkbox"
    id="autoJobScheduleDetection"
    name="autoJobScheduleDetection"
    value="1"
/>

<label
    for=""
/>Crawling</label>

<input
    type="checkbox"
    id="autoJobScheduleCrawling"
    name="autoJobScheduleCrawling"
    value="1"
/>

<label
    for=""
/>Post-procesisng</label>

<input
    type="checkbox"
    id="autoJobSchedulePostProcessing"
    name="autoJobSchedulePostProcessing"
    value="1"
/>

<label
    for=""
/>Deploy</label>

<input
    type="checkbox"
    id="autoJobScheduleDeploy"
    name="autoJobScheduleDeploy"
    value="1"
/>

<br>
<br>

<button class="button btn-primary">Save job automation settings</button>

<hr>


<h3>Process jobs queue on schedule</h3>

<p>Use WP-Cron, regular CRON or other method to process WP2Static's job queue.</p>

<label
    for=""
/>WP-Cron will process job queue every (n) minutes</label>

<input
    type="number"
    id="processQueueInterval"
    name="processQueueInterval"
    value="5"
/>

<br>
<br>

<button class="button btn-primary">Save job automation settings</button>

<hr>

<h3>Manually enqueue new job</h3>

<input name="add_job_detect" id="add_job_detect"  value="1" type="checkbox" />
<label for="add_job_detect">Detect</label>

<input name="add_job_crawl" id="add_job_crawl"  value="1" type="checkbox" />
<label for="add_job_crawl">Crawl</label>

<input name="add_job_post_process" id="add_job_post_process"  value="1" type="checkbox" />
<label for="add_job_post_process">Post process</label>

<input name="add_job_deploy" id="add_job_deploy"  value="1" type="checkbox" />
<label for="add_job_deploy">Deploy</label>

<button class="button">Enqueue job</button>

<hr>


<h3>Job Queue/History</h3>

<p><i><a href="<?php echo admin_url('admin.php?page=wp2static-jobs'); ?>">Refresh page</a> to see latest status</i><p>

<hr>

<table style="width:100%;text-align:center;">
    <thead>
        <tr>
            <th>Date</th>
            <th>Job</th>
            <th>Status</th>
            <th>Duration</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($view['jobs'] as $job): ?>
        <tr>
            <td><?php echo $job->created_at; ?></td>
            <td><?php echo $job->job_type; ?></td>
            <td><?php echo $job->status; ?></td>
            <td><?php echo $job->duration; ?></td>
            <td><a href="#">Cancel</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

