<a name="core-detection-options"></a>

<hr>

<h2>Detection Options</h2>


<h4>Control Detected URLs</h4>

<p>Control which URLs from this WordPress site we want to crawl to generate our static site.</p>

<table class="striped widefat">
    <thead>
        <tr>
            <th>URL Type</th>
            <th>Include in detection</th>
        </tr>
    </thead>
    <tbody>

<?php foreach ($view['detectionOptions'] as $detectionOption): ?>

    <tr>
        <td>
            <label
                for="<?php echo $detectionOption['Option name']; ?>"
            ><?php echo $detectionOption['Option name']; ?></label>
        </td>
        <td style="text-align:center;">
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
  
<h4>Exclude URL Patterns</h4>

<p>After checking the Initial Crawl List, add any paths to filter out here.</p>

<p>WP2Static automatically filters out common backup plugin directories, but please review your initial crawl list to ensure no unwanted URLs are detectected.</p>

<p>You can enter this as a partial string or full path (wildcards/regex not currently supported)</p>

<pre>
<code>.zip</code>
<code>768x768.jpg</code>
<code><?php echo $view['site_info']['site_url']; ?>/wp-content/themes/twentyseventeen/banana.jpg</code>
<code>/my_pricelist.pdf</code>
</pre>

<textarea class="widefat" name="excludeURLs" id="excludeURLs" rows="5" cols="10"><?php echo $this->options->excludeURLs ? $this->options->excludeURLs : ''; ?></textarea>

<h4>Force-include URLs</h4>

<p>Where the plugin fails to detect certain URLs that you know you want to include, please add these here. This will be applied after any exclusions, in case you want to exclude a whole directory, then include just one file from it.</p>

<p><em>Supported formats are site root relative URLs</em></p>

<pre>
<code><?php echo $view['site_info']['site_url']; ?></code>
<code><?php echo $view['site_info']['site_url']; ?>/wp-content/themes/twentyseventeen/banana.jpg</code>
<code>my_pricelist.pdf</code>
</pre>

<textarea class="widefat" name="additionalUrls" id="additionalUrls" rows="5" cols="10"><?php echo $this->options->additionalUrls ? $this->options->additionalUrls : ''; ?></textarea>
