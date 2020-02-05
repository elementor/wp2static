<h2>Crawling Options</h2>

<table class="widefat striped">
    <tbody>
        <?php foreach ($view['crawlingOptions'] as $crawlingOption): ?>
        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $crawlingOption['Option name']; ?>"
                ><?php echo formatDetectionOption( $crawlingOption['Option name'] ); ?></label>
            </td>
            <td>
                <input
                    style="width:100%;"
                    id="<?php echo $crawlingOption['Option name']; ?>"
                    name="<?php echo $crawlingOption['Option name']; ?>"
                    value="<?php echo $crawlingOption['Value'] !== '' ? $crawlingOption['Value'] : ''; ?>"
                />
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
