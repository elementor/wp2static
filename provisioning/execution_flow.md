# Execution flow

Describe the flow of execution. Helpful in understanding, troubleshooting and optimizing the plugin.

 - init plugin
 - activate
 - render options page
  - generate initial crawl list (async)
 - start export
  - cleanup working files
  - cleanup leftover archives
  - start export (ambiguous)
  - crawl site
  - post_process_archive_dir
  - create zip
  - deploy
  - post export teardown  

