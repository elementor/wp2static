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

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['crawlingOptions']['includeDiscoveredAssets']->name; ?>"
                ><?php echo $view['crawlingOptions']['includeDiscoveredAssets']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['crawlingOptions']['includeDiscoveredAssets']->name; ?>"
                    name="<?php echo $view['crawlingOptions']['includeDiscoveredAssets']->name; ?>"
                    value="1"
                    type="checkbox"
                    <?php echo (int) $view['crawlingOptions']['includeDiscoveredAssets']->value === 1 ? 'checked' : ''; ?>
                />
            </td>
        </tr>

    </tbody>
</table>
