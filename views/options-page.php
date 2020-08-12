<?php
/**
 * @package WP2Static
 *
 * Copyright (c) 2011 Leon Stafford
 */

function displayTextfield($a = null, $b = null, $c = null, $d = null, $e = null) {
 echo 'something';
}

function displayCheckbox($a = null, $b = null, $c = null) {
 echo 'something';
}

?>
<div class="wrap">
    <form
        name="wp2static-ui-options"
        method="POST"
        action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

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
                    for="<?php echo $view['coreOptions']['detectCustomPostTypes']->name; ?>"
                ><?php echo $view['coreOptions']['detectCustomPostTypes']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['coreOptions']['detectCustomPostTypes']->name; ?>"
                    name="<?php echo $view['coreOptions']['detectCustomPostTypes']->name; ?>"
                    value="1"
                    type="checkbox"
                    <?php echo (int) $view['coreOptions']['detectCustomPostTypes']->value === 1 ? 'checked' : ''; ?>
                />
            </td>
        </tr>

        <tr>
            <td>
                <label
                    for="<?php echo $view['coreOptions']['detectPages']->name; ?>"
                ><?php echo $view['coreOptions']['detectPages']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['coreOptions']['detectPages']->name; ?>"
                    name="<?php echo $view['coreOptions']['detectPages']->name; ?>"
                    value="1"
                    type="checkbox"
                    <?php echo (int) $view['coreOptions']['detectPages']->value === 1 ? 'checked' : ''; ?>
                />
            </td>
        </tr>

        <tr>
            <td>
                <label
                    for="<?php echo $view['coreOptions']['detectPosts']->name; ?>"
                ><?php echo $view['coreOptions']['detectPosts']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['coreOptions']['detectPosts']->name; ?>"
                    name="<?php echo $view['coreOptions']['detectPosts']->name; ?>"
                    value="1"
                    type="checkbox"
                    <?php echo (int) $view['coreOptions']['detectPosts']->value === 1 ? 'checked' : ''; ?>
                />
            </td>
        </tr>

        <tr>
            <td>
                <label
                    for="<?php echo $view['coreOptions']['detectUploads']->name; ?>"
                ><?php echo $view['coreOptions']['detectUploads']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['coreOptions']['detectUploads']->name; ?>"
                    name="<?php echo $view['coreOptions']['detectUploads']->name; ?>"
                    value="1"
                    type="checkbox"
                    <?php echo (int) $view['coreOptions']['detectUploads']->value === 1 ? 'checked' : ''; ?>
                />
            </td>
        </tr>

        </tbody>
    </table>

    <h2>Crawling Options</h2>

    <table class="widefat striped">
        <tbody>

            <tr>
                <td style="width:50%;">
                    <label
                        for="<?php echo $view['coreOptions']['basicAuthUser']->name; ?>"
                    ><?php echo $view['coreOptions']['basicAuthUser']->label; ?></label>
                </td>
                <td>
                    <input
                        class="widefat"
                        id="<?php echo $view['coreOptions']['basicAuthUser']->name; ?>"
                        name="<?php echo $view['coreOptions']['basicAuthUser']->name; ?>"
                        type="text"
                        value="<?php echo $view['coreOptions']['basicAuthUser']->value !== '' ? $view['coreOptions']['basicAuthUser']->value : ''; ?>"
                    />
                </td>
            </tr>

            <tr>
                <td style="width:50%;">
                    <label
                        for="<?php echo $view['coreOptions']['basicAuthPassword']->name; ?>"
                    ><?php echo $view['coreOptions']['basicAuthPassword']->label; ?></label>
                </td>
                <td>
                    <input
                        class="widefat"
                        id="<?php echo $view['coreOptions']['basicAuthPassword']->name; ?>"
                        name="<?php echo $view['coreOptions']['basicAuthPassword']->name; ?>"
                        type="password"
                        value="<?php echo $view['coreOptions']['basicAuthPassword']->value !== '' ? $view['coreOptions']['basicAuthPassword']->value : ''; ?>"
                    />
                </td>
            </tr>

            <tr>
                <td>
                    <label
                        for="<?php echo $view['coreOptions']['useCrawlCaching']->name; ?>"
                    ><?php echo $view['coreOptions']['useCrawlCaching']->label; ?></label>
                </td>
                <td>
                    <input
                        id="<?php echo $view['coreOptions']['useCrawlCaching']->name; ?>"
                        name="<?php echo $view['coreOptions']['useCrawlCaching']->name; ?>"
                        value="1"
                        type="checkbox"
                        <?php echo (int) $view['coreOptions']['useCrawlCaching']->value === 1 ? 'checked' : ''; ?>
                    />
                </td>
            </tr>

        </tbody>
    </table>

    <h2>Post-processing Options</h2>

    <table class="widefat striped">
        <tbody>
            <tr>
                <td style="width:50%;">
                    <label
                        for="<?php echo $view['coreOptions']['deploymentURL']->name; ?>"
                    ><?php echo $view['coreOptions']['deploymentURL']->label; ?></label>
                </td>
                <td>
                    <input
                        class="widefat"
                        id="<?php echo $view['coreOptions']['deploymentURL']->name; ?>"
                        name="<?php echo $view['coreOptions']['deploymentURL']->name; ?>"
                        type="text"
                        value="<?php echo $view['coreOptions']['deploymentURL']->value !== '' ? $view['coreOptions']['deploymentURL']->value : ''; ?>"
                    />
                </td>
            </tr>
        </tbody>
    </table>

    <h2>Deployment Options</h2>

    <table class="widefat striped">
        <tbody>
            <tr>
                <td style="width:50%;">
                    <label
                        for="<?php echo $view['coreOptions']['completionEmail']->name; ?>"
                    ><?php echo $view['coreOptions']['completionEmail']->label; ?></label>
                </td>
                <td>
                    <input
                        class="widefat"
                        type="email"
                        id="<?php echo $view['coreOptions']['completionEmail']->name; ?>"
                        name="<?php echo $view['coreOptions']['completionEmail']->name; ?>"
                        value="<?php echo $view['coreOptions']['completionEmail']->value !== '' ? $view['coreOptions']['completionEmail']->value : ''; ?>"
                    />
                </td>
            </tr>
            <tr>
                <td style="width:50%;">
                    <label
                        for="<?php echo $view['coreOptions']['completionWebhook']->name; ?>"
                    ><?php echo $view['coreOptions']['completionWebhook']->label; ?></label>
                </td>
                <td>
                    <input
                        style="width:80%;"
                        type="url"
                        id="completionWebhook"
                        name="completionWebhook"
                        value="<?php echo $view['coreOptions']['completionWebhook']->value !== '' ? $view['coreOptions']['completionWebhook']->value : ''; ?>"
                    />

                    <select
                        id="<?php echo $view['coreOptions']['completionWebhookMethod']->name; ?>"
                        name="<?php echo $view['coreOptions']['completionWebhookMethod']->name; ?>"
                        >
                        <option
                            value="POST"
                            <?php echo $view['coreOptions']['completionWebhookMethod']->value === 'POST' ? 'selected' : ''; ?>
                            >POST</option>
                        <option
                            value="GET"
                            <?php echo $view['coreOptions']['completionWebhookMethod']->value === 'GET' ? 'selected' : ''; ?>
                            >GET</option>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>

    <br>

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_ui_save_options" />

    <button class="button btn-primary" type="submit">Save options</button>

    </form>
</div>
