<h2>Detection Options</h2>

<h4>Control Detected URLs</h4>

<p>WP2Static will crawl these WordPress URLs to generate a static site.</p>

<table class="striped widefat">
    <thead>
        <tr>
            <th style="width:50%;">URL Type</th>
            <th>Include in detection</th>
        </tr>
    </thead>
    <tbody>

    <tr>
        <td>
            <label
                for="<?php echo $view['detectionOptions']['detectCustomPostTypes']->name; ?>"
            ><?php echo $view['detectionOptions']['detectCustomPostTypes']->label; ?></label>
        </td>
        <td>
            <input
                id="<?php echo $view['detectionOptions']['detectCustomPostTypes']->name; ?>"
                name="<?php echo $view['detectionOptions']['detectCustomPostTypes']->name; ?>"
                value="1"
                type="checkbox"
                <?php echo (int) $view['detectionOptions']['detectCustomPostTypes']->value === 1 ? 'checked' : ''; ?>
            />
        </td>
    </tr>

    <tr>
        <td>
            <label
                for="<?php echo $view['detectionOptions']['detectPages']->name; ?>"
            ><?php echo $view['detectionOptions']['detectPages']->label; ?></label>
        </td>
        <td>
            <input
                id="<?php echo $view['detectionOptions']['detectPages']->name; ?>"
                name="<?php echo $view['detectionOptions']['detectPages']->name; ?>"
                value="1"
                type="checkbox"
                <?php echo (int) $view['detectionOptions']['detectPages']->value === 1 ? 'checked' : ''; ?>
            />
        </td>
    </tr>

    <tr>
        <td>
            <label
                for="<?php echo $view['detectionOptions']['detectPosts']->name; ?>"
            ><?php echo $view['detectionOptions']['detectPosts']->label; ?></label>
        </td>
        <td>
            <input
                id="<?php echo $view['detectionOptions']['detectPosts']->name; ?>"
                name="<?php echo $view['detectionOptions']['detectPosts']->name; ?>"
                value="1"
                type="checkbox"
                <?php echo (int) $view['detectionOptions']['detectPosts']->value === 1 ? 'checked' : ''; ?>
            />
        </td>
    </tr>

    <tr>
        <td>
            <label
                for="<?php echo $view['detectionOptions']['detectUploads']->name; ?>"
            ><?php echo $view['detectionOptions']['detectUploads']->label; ?></label>
        </td>
        <td>
            <input
                id="<?php echo $view['detectionOptions']['detectUploads']->name; ?>"
                name="<?php echo $view['detectionOptions']['detectUploads']->name; ?>"
                value="1"
                type="checkbox"
                <?php echo (int) $view['detectionOptions']['detectUploads']->value === 1 ? 'checked' : ''; ?>
            />
        </td>
    </tr>

    </tbody>
</table>

