<h2>Deployment Options</h2>

<table class="widefat striped">
    <tbody>
        <tr>
            <td style="width:50%;">
                <label
                    for="completionEmail"
                >Send completion email</label>
            </td>
            <td>
                <input
                    style="width:100%;"
                    id="completionEmail"
                    name="completionEmail"
                    value="<?php echo $view['completionEmail'] !== '' ? $view['completionEmail'] : ''; ?>"
                />
            </td>
        </tr>
        <tr>
            <td style="width:50%;">
                <label
                    for="completionWebhook"
                >Send completion webhook</label>
            </td>
            <td>
                <input
                    style="width:80%;"
                    id="completionWebhook"
                    name="completionWebhook"
                    value="<?php echo $view['completionWebhook'] !== '' ? $view['completionWebhook'] : ''; ?>"
                />

                <select>
                    <option>POST</option>
                    <option>GET</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>

<br>
