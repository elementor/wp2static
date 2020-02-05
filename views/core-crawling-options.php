<a name="core-crawling-options"></a>

<hr>

<h2>Crawling Options</h2>

<br>

<?php foreach ($view['crawlingOptions'] as $crawlingOption): ?>

    <label
        for="<?php echo $crawlingOption['Option name']; ?>"
    ><?php echo formatDetectionOption( $crawlingOption['Option name'] ); ?></label>

    <input
        id="<?php echo $crawlingOption['Option name']; ?>"
        name="<?php echo $crawlingOption['Option name']; ?>"
        value="<?php echo $crawlingOption['Value'] !== '' ? $crawlingOption['Value'] : ''; ?>"
    />

    <br>
    <br>

<?php endforeach; ?>
