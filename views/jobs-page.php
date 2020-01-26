<h2>WP2Static > Jobs</h2>

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

