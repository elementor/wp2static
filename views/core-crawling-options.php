<h2>Crawling Options</h2>

<table class="widefat striped">
    <tbody>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['crawlingOptions']['basicAuthUser']->name; ?>"
                ><?php echo $view['crawlingOptions']['basicAuthUser']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['crawlingOptions']['basicAuthUser']->name; ?>"
                    name="<?php echo $view['crawlingOptions']['basicAuthUser']->name; ?>"
                    type="text"
                    value="<?php echo $view['crawlingOptions']['basicAuthUser']->value !== '' ? $view['crawlingOptions']['basicAuthUser']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['crawlingOptions']['basicAuthPassword']->name; ?>"
                ><?php echo $view['crawlingOptions']['basicAuthPassword']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['crawlingOptions']['basicAuthPassword']->name; ?>"
                    name="<?php echo $view['crawlingOptions']['basicAuthPassword']->name; ?>"
                    type="password"
                    value="<?php echo $view['crawlingOptions']['basicAuthPassword']->value !== '' ? $view['crawlingOptions']['basicAuthPassword']->value : ''; ?>"
                />
            </td>
        </tr>

    </tbody>
</table>
