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

<?php

function formatDetectionOption( $string ) {
    $pieces = preg_split( '/(?=[A-Z])/', $string );
    $word = implode( " ", $pieces );

    return ucwords( str_replace( "detect", "", $word ) );
}

?>


<?php foreach ($view['detectionOptions'] as $detectionOption): ?>

    <tr>
        <td>
            <label
                for="<?php echo $detectionOption['Option name']; ?>"
            ><?php echo formatDetectionOption( $detectionOption['Option name'] ); ?></label>
        </td>
        <td>
            <input
                id="<?php echo $detectionOption['Option name']; ?>"
                name="<?php echo $detectionOption['Option name']; ?>"
                value="1"
                type="checkbox"
                <?php echo $detectionOption['Value'] === 1 ? 'checked' : ''; ?>
            />
        </td>
    </tr>

<?php endforeach; ?>

    </tbody>
</table>

