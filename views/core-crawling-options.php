<hr>

<h2>Crawling Options</h2>

<table class="widefat striped">
    <thead>
        <tr>
            <th style="width:50%;">Option</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($view['crawlingOptions'] as $crawlingOption): ?>
        <tr>
            <td>
                <label
                    for="<?php echo $crawlingOption['Option name']; ?>"
                ><?php echo formatDetectionOption( $crawlingOption['Option name'] ); ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $crawlingOption['Option name']; ?>"
                    name="<?php echo $crawlingOption['Option name']; ?>"
                    value="<?php echo $crawlingOption['Value'] !== '' ? $crawlingOption['Value'] : ''; ?>"
                />
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
