<h2>Post-processing Options</h2>

<table class="widefat striped">
    <tbody>
        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['postProcessingOptions']['deploymentURL']->name; ?>"
                ><?php echo $view['postProcessingOptions']['deploymentURL']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['postProcessingOptions']['deploymentURL']->name; ?>"
                    name="<?php echo $view['postProcessingOptions']['deploymentURL']->name; ?>"
                    type="text"
                    value="<?php echo $view['postProcessingOptions']['deploymentURL']->value !== '' ? $view['postProcessingOptions']['deploymentURL']->value : ''; ?>"
                />
            </td>
        </tr>
    </tbody>
</table>
