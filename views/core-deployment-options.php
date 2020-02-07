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
                        <?php echo $view['deploymentOptions']['completionWebhookMethod']->value === 'POST' ? 'checked' : ''; ?>
                        >POST</option>
                    <option
                        value="GET"
                        <?php echo $view['deploymentOptions']['completionWebhookMethod']->value === 'GET' ? 'checked' : ''; ?>
                        >GET</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>

<br>
