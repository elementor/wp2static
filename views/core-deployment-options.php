<h2>Deployment Options</h2>

<table class="widefat striped">
    <tbody>
        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['deploymentOptions']['completionEmail']->name; ?>"
                ><?php echo $view['deploymentOptions']['completionEmail']->label; ?></label>
            </td>
            <td>
                <input
                    style="width:100%;"
                    type="email"
                    id="<?php echo $view['deploymentOptions']['completionEmail']->name; ?>"
                    name="<?php echo $view['deploymentOptions']['completionEmail']->name; ?>"
                    value="<?php echo $view['deploymentOptions']['completionEmail']->value !== '' ? $view['deploymentOptions']['completionEmail']->value : ''; ?>"
                />
            </td>
        </tr>
        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['deploymentOptions']['completionWebhook']->name; ?>"
                ><?php echo $view['deploymentOptions']['completionWebhook']->label; ?></label>
            </td>
            <td>
                <input
                    style="width:80%;"
                    type="url"
                    id="completionWebhook"
                    name="completionWebhook"
                    value="<?php echo $view['deploymentOptions']['completionWebhook']->value !== '' ? $view['deploymentOptions']['completionWebhook']->value : ''; ?>"
                />

                <select
                    id="<?php echo $view['deploymentOptions']['completionWebhookMethod']->name; ?>"
                    name="<?php echo $view['deploymentOptions']['completionWebhookMethod']->name; ?>"
                    >
                    <option
                        value="POST"
                        <?php echo $view['deploymentOptions']['completionWebhookMethod']->value === 'POST' ? 'selected' : ''; ?>
                        >POST</option>
                    <option
                        value="GET"
                        <?php echo $view['deploymentOptions']['completionWebhookMethod']->value === 'GET' ? 'selected' : ''; ?>
                        >GET</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>

<br>
