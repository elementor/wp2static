declare var wp2staticString: any;
declare var ajaxurl: string;
import $ from "jquery";

var formProcessors = {
  basin: {
    name: 'Basin',
    placeholder: 'https://usebasin.com/f/',
    website: 'https://usebasin.com'
  },
  formspree: {
    name: 'Formspree',
    placeholder: 'https://formspree.io/myemail@domain.com',
    website: 'https://formspree.io',
    description: 'FormSpree is very simple to start with, just set your endpoint, including your email address and start sending.'
  },
  zapier: {
    name: 'Zapier',
    placeholder: 'https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/',
    website: 'https://zapier.com'
  },
  formkeep: {
    name: 'FormKeep',
    placeholder: 'https://formkeep.com/f/5dd8de73ce2c',
    website: 'https://formkeep.com'
  },
  custom: {
    name: 'Custom endpoint',
    placeholder: 'https://mycustomendpoint.com/SOMEPATH',
    website: 'https://docs.wp2static.com'
  }
}
var validationErrors = ''
var deployOptions = {
  zip: {
    exportSteps: [
      'finalize_deployment'
    ],
    requiredFields: {
    }
  },
  folder: {
    exportSteps: [
      'finalize_deployment'
    ],
    requiredFields: {
    }
  }
}
var spinner
var siteInfo = JSON.parse(wp2staticString.siteInfo)
var currentDeploymentMethod
if (wp2staticString.currentDeploymentMethod) {
  currentDeploymentMethod = wp2staticString.currentDeploymentMethod
} else {
  currentDeploymentMethod = 'folder'
}

// TODO: get the log out of the archive, along with it's meta infos
var logFileUrl = siteInfo.uploads_url + 'wp2static-working-files/EXPORT-LOG.txt'
var selectedFormProcessor = ''
var exportAction = ''
var exportTargets = []
var exportCommenceTime = ''
var statusText = ''
var protocolAndDomainRE = /^(?:\w+:)?\/\/(\S+)$/
var localhostDomainRE = /^localhost[:?\d]*(?:[^:?\d]\S*)?$/
var nonLocalhostDomainRE = /^[^\s.]+\.\S{2,}$/
var timerIntervalID = ''
var statusDescriptions = {
  'crawl_site': 'Crawling initial file list',
  'post_process_archive_dir': 'Processing the crawled files',
  'post_export_teardown': 'Cleaning up after processing'
}
jQuery(document).ready(
  function ($) {
    function generateFileListSuccessCallback (serverResponse) {
      if (!serverResponse) {
        $('#current_action').html('Failed to generate initial file list. Please <a href="https://docs.wp2static.com" target="_blank">contact support</a>')
        $('.pulsate-css').hide()
      } else {
        $('#initial_crawl_list_loader').hide()
        $('#initial_crawl_list_count').text(serverResponse + ' URLs were detected on your site that will be used to initiate the crawl. Other URLs will be discovered while crawling.')
        $('#preview_initial_crawl_list_button').show()

        $('#startExportButton').prop('disabled', false)
        $('.saveSettingsButton').prop('disabled', false)
        $('.resetDefaultSettingsButton').prop('disabled', false)
        $('#current_action').html(serverResponse + ' URLs were detected for initial crawl list. <a href="#" id="GoToDetectionTabButton">Adjust detection via the URL Detection tab.</a>')
        $('.pulsate-css').hide()
      }
    }

    function generateFileListFailCallback (serverResponse) {
      const failedDeployMessage = 'Failed to generate Initial Crawl List. Please check your permissions to the WordPress upload directory or check your Export Log in case of more info.'

      $('#current_action').html(failedDeployMessage)
      $('.pulsate-css').hide()
      $('#startExportButton').prop('disabled', true)
      $('.saveSettingsButton').prop('disabled', false)
      $('.resetDefaultSettingsButton').prop('disabled', false)
      $('.cancelExportButton').hide()
      $('#initial_crawl_list_loader').hide()
    }

    function prepareInitialFileList () {
      statusText = 'Analyzing site... this may take a few minutes (but it\'s worth it!)'
      $('#current_action').html(statusText)

      sendWP2StaticAJAX(
        'generate_filelist_preview',
        generateFileListSuccessCallback,
        generateFileListFailCallback
      )
    }

    function sendWP2StaticAJAX (ajaxAction, successCallback, failCallback) {
      $('.hiddenActionField').val('wp_static_html_output_ajax')
      $('#hiddenAJAXAction').val(ajaxAction)
      $('#progress').show()
      $('.pulsate-css').show()

      const data = $('.options-form :input')
        .filter(
          function (index, element) {
            return $(element).val() !== ''
          }
        )
        .serialize()

      $.ajax(
        {
          url: ajaxurl,
          data: data,
          dataType: 'html',
          method: 'POST',
          success: successCallback,
          error: failCallback
        }
      )
    }

    function saveOptionsSuccessCallback (serverResponse) {
      $('#progress').hide()

      location.reload()
    }

    function saveOptionsFailCallback (serverResponse) {
      $('#progress').hide()

      location.reload()
    }

    function saveOptions () {
      $('#current_action').html('Saving options')
      sendWP2StaticAJAX(
        'save_options',
        saveOptionsSuccessCallback,
        saveOptionsFailCallback
      )
    }

    function millisToMinutesAndSeconds (millis) {
      var minutes = Math.floor(millis / 60000)
      var seconds = ((millis % 60000) / 1000).toFixed(0)
      return minutes + ':' + (seconds < 10 ? '0' : '') + seconds
    }

    function processExportTargets () {
      if (exportTargets.length > 0) {
        const target = exportTargets.shift()
        const exportSteps = deployOptions[target]['exportSteps']

        doAJAXExport(exportSteps)
      } else {
        // if zip was selected, call to get zip name and enable the button with the link to download
        if (currentDeploymentMethod === 'zip') {
          const zipURL = siteInfo.uploads_url + 'wp2static-exported-site.zip?cacheBuster=' + Date.now()
          $('#downloadZIP').attr('href', zipURL)
          $('#downloadZIP').show()
        } else {
          // for other methods, show the Go to my static site link
          $('#goToMyStaticSite').attr('href', $('#baseUrl').val())
          $('#goToMyStaticSite').show()
        }

        // all complete
        const exportCompleteTime = +new Date()
        const exportDuration = exportCompleteTime - exportCommenceTime

        // clear export commence time for next run
        exportCommenceTime = ''

        stopTimer()
        $('#current_action').text('Process completed in ' + millisToMinutesAndSeconds(exportDuration) + ' (mins:ss)')
        $('#goToMyStaticSite').focus()
        $('.pulsate-css').hide()
        $('#startExportButton').prop('disabled', false)
        $('.saveSettingsButton').prop('disabled', false)
        $('.resetDefaultSettingsButton').prop('disabled', false)
        $('.cancelExportButton').hide()
        notifyMe()
      }
    }

    function downloadExportLogSuccessCallback (serverResponse) {
      if (!serverResponse) {
        $('#current_action').html('Failed to download Export Log <a id="downloadExportLogButton" href="#">try again</a>')
        $('.pulsate-css').hide()
      } else {
        $('#current_action').html('Download <a href="' + serverResponse + '"> ' + serverResponse + '</a>')
        $('.pulsate-css').hide()
      }
    }

    function downloadExportLogFailCallback (serverResponse) {
      $('.pulsate-css').hide()
      $('#current_action').html('Failed to download Export Log <a id="downloadExportLogButton" href="#">try again</a>')
    }

    function deleteCrawlCacheSuccessCallback (serverResponse) {
      if (!serverResponse) {
        $('.pulsate-css').hide()
        $('#current_action').html('Failed to delete Crawl Cache.')
      } else {
        $('#current_action').html('Crawl Cache successfully deleted.')
        $('.pulsate-css').hide()
      }
    }

    function deleteCrawlCacheFailCallback (serverResponse) {
      $('.pulsate-css').hide()
      $('#current_action').html('Failed to delete Crawl Cache.')
    }

    function downloadExportLog () {
      $('#current_action').html('Downloading Export Log...')

      sendWP2StaticAJAX(
        'download_export_log',
        downloadExportLogSuccessCallback,
        downloadExportLogFailCallback
      )
    }

    $(document).on(
      'click',
      '#detectEverythingButton',
      function (evt) {
        evt.preventDefault()
        $('#detectionOptionsTable input[type="checkbox"]').attr('checked', true)
      }
    )

    $(document).on(
      'click',
      '#deleteCrawlCache',
      function (evt) {
        evt.preventDefault()
        $('#current_action').html('Deleting Crawl Cache...')

        sendWP2StaticAJAX(
          'delete_crawl_cache',
          deleteCrawlCacheSuccessCallback,
          deleteCrawlCacheFailCallback
        )
      }
    )

    $(document).on(
      'click',
      '#detectNothingButton',
      function (evt) {
        evt.preventDefault()
        $('#detectionOptionsTable input[type="checkbox"]').attr('checked', false)
      }
    )

    $(document).on(
      'click',
      '#downloadExportLogButton',
      function (evt) {
        evt.preventDefault()
        downloadExportLog()
      }
    )

    function ajaxErrorHandler () {
      stopTimer()

      const failedDeployMessage = 'Failed during "' + statusText +
              '", <button id="downloadExportLogButton">Download export log</button>'

      $('#current_action').html(failedDeployMessage)
      $('.pulsate-css').hide()
      $('#startExportButton').prop('disabled', false)
      $('.saveSettingsButton').prop('disabled', false)
      $('.resetDefaultSettingsButton').prop('disabled', false)
      $('.cancelExportButton').hide()
    }

    function startExportSuccessCallback (serverResponse) {
      var initialSteps = [
        'crawl_site',
        'post_process_archive_dir'
      ]

      doAJAXExport(initialSteps)
    }

    function startTimer () {
      timerIntervalID = window.setInterval(updateTimer, 1000)
    }

    function stopTimer () {
      window.clearInterval(timerIntervalID)
    }

    function updateTimer () {
      const exportCompleteTime = +new Date()
      const runningTime = exportCompleteTime - exportCommenceTime

      $('#export_timer').html(
        '<b>Export duration: </b>' + millisToMinutesAndSeconds(runningTime)
      )
    }

    function startExport () {
      // start timer
      exportCommenceTime = +new Date()
      startTimer()

      // startPolling();

      validationErrors = getValidationErrors()

      if (validationErrors !== '') {
        alert(validationErrors)

        // TODO: place in function that resets any in progress counters, etc
        $('#progress').hide()
        $('#startExportButton').prop('disabled', false)
        $('.saveSettingsButton').prop('disabled', false)
        $('.resetDefaultSettingsButton').prop('disabled', false)
        $('.cancelExportButton').hide()

        return false
      }

      $('#current_action').html('Starting export...')

      // reset export targets to avoid having left-overs from a failed run
      exportTargets = []

      if (currentDeploymentMethod === 'zip') {
        $('#createZip').attr('checked', 'checked')
      }
      exportTargets.push(currentDeploymentMethod)

      sendWP2StaticAJAX(
        'prepare_for_export',
        startExportSuccessCallback,
        ajaxErrorHandler
      )
    }

    function clearProgressAndResults () {
      $('#downloadZIP').hide()
      $('#goToMyStaticSite').hide()
      $('#exportDuration').hide()
    }

    function getValidationErrors () {
      var validationErrors = ''
      // check for when targetFolder is showing (plugin reset state)
      if ($('#targetFolder').is(':visible') &&
            ($('#targetFolder').val() === '')) {
        validationErrors += 'Target folder may not be empty. Please adjust your settings.'
      }

      if (($('#baseUrl').val() === undefined ||
            $('#baseUrl').val() === '') &&
            !$('#allowOfflineUsage').is(':checked')) {
        validationErrors += 'Please set the Base URL field to the address you will host your static site.\n'
      }

      // TODO: on new Debian package-managed environment, this was falsely erroring
      if (!isUrl($('#baseUrl').val()) && !$('#allowOfflineUsage').is(':checked')) {
        // TODO: testing / URL as base
        if ($('#baseUrl').val() !== '/') {
          validationErrors += 'Please set the Base URL field to a valid URL, ie http://mystaticsite.com.\n'
        }
      }

      const requiredFields =
            deployOptions[currentDeploymentMethod]['requiredFields']

      if (requiredFields) {
        validateEmptyFields(requiredFields)
      }

      const repoField = deployOptions[currentDeploymentMethod]['repoField']

      if (repoField) {
        validateRepoField(repoField)
      }

      return validationErrors
    }

    function validateRepoField (repoField) {
      const repo = $('#' + repoField['field'] + '').val()

      if (repo !== '') {
        if (repo.split('/').length !== 2) {
          validationErrors += repoField['message']
        }
      }
    }

    function validateEmptyFields (requiredFields) {
      Object.keys(requiredFields).forEach(
        function (key, index) {
          if ($('#' + key).val() === '') {
            validationErrors += requiredFields[key] + '\n'
          }
        }
      )
    }

    function isUrl (string) {
      if (typeof string !== 'string') {
        return false
      }

      var match = string.match(protocolAndDomainRE)

      if (!match) {
        return false
      }

      var everythingAfterProtocol = match[1]

      if (!everythingAfterProtocol) {
        return false
      }

      if (localhostDomainRE.test(everythingAfterProtocol) ||
            nonLocalhostDomainRE.test(everythingAfterProtocol)) {
        return true
      }

      return false
    }

    /*
        doAJAXExport() can handle from 1 to n actions
        each action runs, with 3 possible results:
        SUCCESS - action is complete
        > 0 - action is in progress inremental task
        ERROR

        if an action is successful, and there are other actions queued up,
        it will call the function again with the remaining arguments/actions

        if an action is succesful, and there are no other actions queued,
        it will call processExportTargets() to continue any other exports

        if an action is in progress incremental, it will call itself again,
        with all the same arguments

        if an action fails, ajaxErrorHandler() is called
        */
    function doAJAXExport (args) {
      exportAction = args[0]
      statusText = exportAction

      if (statusDescriptions[exportAction] !== undefined) {
        statusText = statusDescriptions[exportAction]
      } else {
        statusText = exportAction
      }

      $('#current_action').html(statusText)
      $('.hiddenActionField').val('wp_static_html_output_ajax')
      $('#hiddenAJAXAction').val(exportAction)

      const data = $('.options-form :input')
        .filter(
          function (index, element) {
            return $(element).val() !== ''
          }
        )
        .serialize()

      $.ajax(
        {
          url: ajaxurl,
          data: data,
          dataType: 'html',
          method: 'POST',
          success: function (serverResponse) {
            // if an action is successful, and there are other actions queued up
            if (serverResponse === 'SUCCESS' && args.length > 1) {
              // rm first action now that it's succeeded
              args.shift()
              // call function with all other actions
              doAJAXExport(args)
              // if an action is in progress incremental, it will call itself again
            } else if (serverResponse > 0) {
              doAJAXExport(args)
            } else if (serverResponse === 'SUCCESS') {
              // not an incremental action, continue on with export targets
              processExportTargets()
            } else {
              ajaxErrorHandler()
            }
          },
          error: ajaxErrorHandler
        }
      )
    }

    function updateBaseURLReferences () {
      var baseUrlPreviews = $('.baseUrl_preview')

      var baseUrl = $('#baseUrl-' + currentDeploymentMethod).val()

      $('#baseUrl').val($('#baseUrl-' + currentDeploymentMethod).val())

      baseUrlPreviews.text(baseUrl.replace(/\/$/, '') + '/')

      // update the clickable preview url in folder options
      $('#folderPreviewURL').text(siteInfo.site_url + '/')
      $('#folderPreviewURL').attr('href', (siteInfo.site_url + '/'))
    }

    function hideOtherVendorMessages () {
      const notices = $('.update-nag, .updated, .error, .is-dismissible, .elementor-message')

      $.each(
        notices,
        function (index, element) {
          if (!$(element).hasClass('wp2static-notice')) {
            $(element).hide()
          }
        }
      )
    }

    function setFormProcessor (selectedFormProcessor) {
      if (selectedFormProcessor in formProcessors) {
        $('#formProcessor_description').text(formProcessors[selectedFormProcessor].description)
        var website = formProcessors[selectedFormProcessor].website
        var websiteLink = $('<a>').attr('href', website).text('Visit ' + formProcessors[selectedFormProcessor].name)
        $('#formProcessor_website').html(websiteLink)
        $('#formProcessor_endpoint').attr('placeholder', formProcessors[selectedFormProcessor].placeholder)
      } else {
        $('#formProcessor_description').text('')
        $('#formProcessor_website').html('')
        $('#formProcessor_endpoint').attr('placeholder', 'Form endpoint')
      }
    }

    function populateFormProcessorOptions (formProcessors) {
      for (const [formProcessor, options] of Object.entries(formProcessors)) {
        var opt = $('<option>').val(formProcessor).text(options.name)
        $('#formProcessor_select').append(opt)
      }
    }

    /*
        TODO: quick win to get the select menu options to behave like the sendViaFTP, etc checkboxes
        */
    // TODO: remove this completely?
    function setDeploymentMethod (selectedDeploymentMethod) {
      // hide zip dl link for all
      $('#downloadZIP').hide()
      currentDeploymentMethod = selectedDeploymentMethod

      // set the selected option in case calling this from outside the event handler
      $('.selected_deployment_method').val(selectedDeploymentMethod)
    }

    function offlineUsageChangeHandler (checkbox) {
      if ($(checkbox).is(':checked')) {
        $('#baseUrl-zip').prop('disabled', true)
      } else {
        $('#baseUrl-zip').prop('disabled', false)
      }
    }

    function setExportSettingDetailsVisibility (changedCheckbox) {
      const checkboxName = $(changedCheckbox).attr('name')
      const exportOptionName = checkboxName.replace('sendVia', '').toLowerCase()
      const exportOptionElements = $('.' + exportOptionName)

      if ($(changedCheckbox).is(':checked')) {
        exportOptionElements.show()
        // unhide all the inputs, the following span and the following br
      } else {
        // hide all the inputs, the following span and the following br
        exportOptionElements.hide()
      }
    }

    /*
        render the information and settings blocks based on the deployment method selected
        */
    function renderSettingsBlock (selectedDeploymentMethod) {
      // hide non-active deployment methods
      $('[class$="_settings_block"]').hide()
      // hide those not selected
      $('.' + selectedDeploymentMethod + '_settings_block').show()
    }

    function notifyMe () {
      if (!Notification) {
        alert('All exports are complete!.')
        return
      }

      if (window.location.protocol === 'https:') {
        if (Notification.permission !== 'granted') {
          Notification.requestPermission()
        } else {
          var notification = new Notification(
            'WP Static HTML Export',
            {
              icon: 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/Wordpress_Shiny_Icon.svg/768px-Wordpress_Shiny_Icon.svg.png',
              body: 'Exports have finished!'
            }
          )

          notification.onclick = function () {
            parent.focus()
            window.focus()
            this.close()
          }
        }
      }
    }

    function reloadLogFile () {
      loadLogFile('export_log')
    }

    function loadLogFile () {
      // display loading icon
      $('#log_load_progress').show()

      $('#export_log_textarea').attr('disabled', true)

      // set textarea content to 'Loading log file...'
      $('#export_log_textarea').html('Loading log file...')

      // load the log file
      $.get(
        logFileUrl + '?cacheBuster=' + Date.now(),
        function (data) {
          // hide loading icon
          $('#log_load_progress').hide()

          // set textarea to enabled
          $('#export_log_textarea').attr('disabled', false)

          // set textarea content
          $('#export_log_textarea').html(data)
        }
      ).fail(
        function () {
          $('#log_load_progress').hide()

          // set textarea to enabled
          $('#export_log_textarea').attr('disabled', false)

          // set textarea content
          $('#export_log_textarea').html('Requested log file not found')
        }
      )
    }

    if (Notification.permission !== 'granted') {
      if (window.location.protocol === 'https:') {
        Notification.requestPermission()
      }
    }

    $('input[type="checkbox"]').change(
      function () {
        setExportSettingDetailsVisibility(this)
      }
    )

    // disable zip base url field when offline usage is checked
    $('#allowOfflineUsage').change(
      function () {
        offlineUsageChangeHandler($(this))
      }
    )

    // handler when form processor is changed
    $('#formProcessor_select').change(
      function () {
        setFormProcessor(this.value)
      }
    )

    // handler when deployment method is changed
    $('.selected_deployment_method').change(
      function () {
        renderSettingsBlock(this.value)
        setDeploymentMethod(this.value)
        updateBaseURLReferences()
        clearProgressAndResults()
      }
    )

    // handler when log selector is changed
    $('#reload_log_button').click(
      function () {
        reloadLogFile()
      }
    )

    // update base url previews in realtime
    $(document).on(
      'input',
      '[id^="baseUrl-"]',
      function () {
        updateBaseURLReferences()
      }
    )

    function changeTab (targetTab) {
      var tabsContentMapping = {
        advanced_settings: 'Advanced Options',
        form_settings: 'Forms',
        production_deploy: 'Production',
        staging_deploy: 'Staging',
        help_troubleshooting: 'Help',
        workflow_tab: 'Workflow',
        export_logs: 'Logs',
        crawl_settings: 'Crawling',
        caching_settings: 'Caching',
        automation_settings: 'Automation',
        url_detection: 'URL Detection',
        processing_settings: 'Processing',
        add_ons: 'Add-ons'
      }

      // switch the active tab
      $.each(
        $('.nav-tab'),
        function (index, element) {
          if ($(element).text() === targetTab) {
            $(element).addClass('nav-tab-active')
            $(element).blur()
          } else {
            $(element).removeClass('nav-tab-active')
          }
        }
      )

      // hide/show the tab content
      for (var key in tabsContentMapping) {
        if (tabsContentMapping.hasOwnProperty(key)) {
          if (tabsContentMapping[key] === targetTab) {
            $('.' + key).show()
            $('html, body').scrollTop(0)
          } else {
            $('.' + key).hide()
          }
        }
      }
    }

    $(document).on(
      'click',
      '#GoToDetectionTabButton',
      function (evt) {
        evt.preventDefault()
        changeTab('URL Detection')
      }
    )

    $(document).on(
      'click',
      '#GoToDeployTabButton,#GoToDeployTabLink',
      function (evt) {
        evt.preventDefault()
        changeTab('Deployment')
      }
    )

    // TODO: create action for #GenerateZIPOfflineUse
    // and #GenerateZIPDeployAnywhere

    $(document).on(
      'click',
      '#GoToAdvancedTabButton',
      function (evt) {
        evt.preventDefault()
        changeTab('Advanced Options')
      }
    )

    $(document).on(
      'click',
      '.nav-tab',
      function (evt) {
        evt.preventDefault()
        changeTab($(this).text())
      }
    )

    $(document).on(
      'submit',
      '#general-options',
      function (evt) {
        evt.preventDefault()
      }
    )

    $(document).on(
      'click',
      '#send_supportRequest',
      function (evt) {
        evt.preventDefault()

        var supportRequest = $('#supportRequestContent').val()

        if ($('#supportRequestIncludeLog').is(':checked')) {
          $.get(
            logFileUrl,
            function (data) {
              supportRequest += '#### EXPORT LOG ###'
              supportRequest += data

              data = {
                email: $('#supportRequestEmail').val(),
                supportRequest: supportRequest
              }

              $.ajax(
                {
                  url: 'https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/',
                  data: data,
                  dataType: 'html',
                  method: 'POST',
                  success: sendSupportSuccessCallback,
                  error: sendSupportFailCallback
                }
              )
            }
          ).fail(
            function () {
              console.log('failed to retrieve export log')
            }
          )
        }

        var data = {
          email: $('#supportRequestEmail').val(),
          supportRequest: supportRequest
        }

        $.ajax(
          {
            url: 'https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/',
            data: data,
            dataType: 'html',
            method: 'POST',
            success: sendSupportSuccessCallback,
            error: sendSupportFailCallback
          }
        )
      }
    )

    $('#startExportButton').click(
      function () {
        clearProgressAndResults()
        $(this).prop('disabled', true)
        $('.saveSettingsButton').prop('disabled', true)
        $('.resetDefaultSettingsButton').prop('disabled', true)
        $('.cancelExportButton').show()
        startExport()
      }
    )

    $('.cancelExportButton').click(
      function () {
        var reallyCancel = confirm('Stop current export and reload page?')
        if (reallyCancel) {
          window.location = window.location.href
        }
      }
    )

    function sendSupportSuccessCallback (serverResponse) {
      alert('Successful support request sent')
    }

    function sendSupportFailCallback (serverResponse) {
      alert('Failed to send support request. Please try again or contact help@wp2static.com.')
    }

    function resetDefaultSettingsSuccessCallback (serverResponse) {
      alert('Settings have been reset to default, the page will now be reloaded.')
      window.location.reload(true)
    }

    function resetDefaultSettingsFailCallback (serverResponse) {
      alert('Error encountered in trying to reset settings. Please try refreshing the page.')
    }

    $('#wp2static-footer').on(
      'click',
      '.resetDefaultSettingsButton',
      function (event) {
        event.preventDefault()

        sendWP2StaticAJAX(
          'reset_default_settings',
          resetDefaultSettingsSuccessCallback,
          resetDefaultSettingsFailCallback
        )
      }
    )

    $('#wp2static-footer').on(
      'click',
      '.saveSettingsButton',
      function (event) {
        event.preventDefault()
        saveOptions()
      }
    )

    function deleteDeployCacheSuccessCallback (serverResponse) {
      if (serverResponse === 'SUCCESS') {
        alert('Deploy cache cleared')
      } else {
        alert('FAIL: Unable to delete deploy cache')
      }

      spinner.hide()
      $('.pulsate-css').hide()
    }

    function deleteDeployCacheFailCallback (serverResponse) {
      alert('FAIL: Unable to delete deploy cache')

      spinner.hide()
      $('.pulsate-css').hide()
    }

    $('.wrap').on(
      'click',
      '#delete_deploy_cache_button',
      function (event) {
        event.preventDefault()
        var button = event.currentTarget
        spinner = $(button).siblings('div.spinner')
        spinner.show()
        sendWP2StaticAJAX(
          'delete_deploy_cache',
          deleteDeployCacheSuccessCallback,
          deleteDeployCacheFailCallback
        )
      }
    )

    function testDeploymentSuccessCallback (serverResponse) {
      if (serverResponse === 'SUCCESS') {
        alert('Connection/Upload Test Successful')
      } else {
        alert('FAIL: Unable to complete test upload to ' + currentDeploymentMethod)
      }

      spinner.hide()
      $('.pulsate-css').hide()
    }

    function testDeploymentFailCallback (serverResponse) {
      alert('FAIL: Unable to complete test upload to ' + currentDeploymentMethod)
      spinner.hide()
      $('.pulsate-css').hide()
    }

    $('.wrap').on(
      'click',
      '[id$="-test-button"]',
      function (event) {
        event.preventDefault()
        spinner = $('button').siblings('div.spinner')
        spinner.show()

        sendWP2StaticAJAX(
          'test_' + currentDeploymentMethod,
          testDeploymentSuccessCallback,
          testDeploymentFailCallback
        )
      }
    )

    $('.wrap').on(
      'click',
      '#save-and-reload',
      function (event) {
        event.preventDefault()
        saveOptions()
      }
    )

    $('.spinner').hide()

    // guard against selected option for add-on not currently activated
    if ($('#baseUrl-' + currentDeploymentMethod).val() === undefined) {
      currentDeploymentMethod = 'folder'
    }

    populateFormProcessorOptions(formProcessors)

    setFormProcessor(selectedFormProcessor)

    // call change handler on page load, to set correct state
    offlineUsageChangeHandler($('#allowOfflineUsage'))

    updateBaseURLReferences($('#baseUrl').val())

    // set and show the previous selected deployment method
    renderSettingsBlock(currentDeploymentMethod)

    // set the select to the current deployment type
    setDeploymentMethod(currentDeploymentMethod)

    // hide all but WP2Static messages
    hideOtherVendorMessages()

    prepareInitialFileList()
  }
)
