declare var wp2staticString: any;
declare var ajaxurl: string;
import { WP2StaticAJAX } from "./WP2StaticAJAX";
import { WP2StaticGlobals } from "./WP2StaticGlobals";

interface FormProcessor {
    id: string;
    name: string;
    placeholder: string;
    website: string;
    description: string;
}

// NOTE: passing around a globals object to allow shared instance and access
// from browser.
// within this entrypoint, access directly. From other classes, this., from
// browser WP2Static.wp2staticGlobals
export const adminPage = new WP2StaticAdminPageModel();
export const wp2staticGlobals = new WP2StaticGlobals();
export const wp2staticAJAX = new WP2StaticAJAX( wp2staticGlobals );

const formProcessors: FormProcessor[] = [
  {
    description: "Basin does stuff",
    id: "basin",
    name: "Basin",
    placeholder: "https://usebasin.com/f/",
    website: "https://usebasin.com",
  },
  {
    description: `FormSpree is very simple to start with, just set your
 endpoint, including your email address and start sending.`,
    id: "formspree",
    name: "Formspree",
    placeholder: "https://formspree.io/myemail@domain.com",
    website: "https://formspree.io",
  },
  {
    description: "Zapier does stuff",
    id: "zapier",
    name: "Zapier",
    placeholder: "https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/",
    website: "https://zapier.com",
  },
  {
    description: "Formkeep does stuff",
    id: "formkeep",
    name: "FormKeep",
    placeholder: "https://formkeep.com/f/5dd8de73ce2c",
    website: "https://formkeep.com",
  },
  {
    description: "Use any custom endpoint",
    id: "custom",
    name: "Custom endpoint",
    placeholder: "https://mycustomendpoint.com/SOMEPATH",
    website: "https://docs.wp2static.com",
  },
];

let validationErrors = "";
let spinner: any;

wp2staticGlobals.siteInfo = JSON.parse(wp2staticString.siteInfo);

if (wp2staticString.currentDeploymentMethod) {
  wp2staticGlobals.currentDeploymentMethod = wp2staticString.currentDeploymentMethod;
} else {
  wp2staticGlobals.currentDeploymentMethod = "folder";
}

// TODO: get the log out of the archive, along with it's meta infos
const logFileUrl = wp2staticGlobals.siteInfo.uploads_url + "wp2static-working-files/EXPORT-LOG.txt";
const selectedFormProcessor = "";
const exportAction = "";
const protocolAndDomainRE = /^(?:\w+:)?\/\/(\S+)$/;
const localhostDomainRE = /^localhost[:?\d]*(?:[^:?\d]\S*)?$/;
const nonLocalhostDomainRE = /^[^\s.]+\.\S{2,}$/;

document.addEventListener("DOMContentLoaded", () => {
    function generateFileListSuccessCallback(serverResponse: any) {
      if (!serverResponse) {
        adminPage.pulsateCSS.style.display = 'none';
        adminPage.currentAction.innerHTML(`Failed to generate initial file list.
 Please <a href="https://docs.wp2static.com" target="_blank">contact support</a>`);
      } else {
        adminPage.initialCrawlListLoader.style.display = 'none';
        adminPage.previewInitialCrawlListButton.style.display = 'block';
        adminPage.pulsateCSS.style.display = 'none';
        adminPage.resetDefaultSettingsButton.setAttribute("disabled", false);
        adminPage.saveSettingsButton.setAttribute("disabled", false);
        adminPage.startExportButton.setAttribute("disabled", false);
        adminPage.currentAction.innerHTML(`${serverResponse} URLs were detected for
 initial crawl list. <a href="#" id="GoToDetectionTabButton">Adjust detection
 via the URL Detection tab.</a>`);
        adminPage.initialCrawlListCount.textContent = `${serverResponse} URLs were
 detected on your site that will be used to initiate the crawl.
 Other URLs will be discovered while crawling.`;
      }
    }

    function generateFileListFailCallback(serverResponse: any) {
      const failedDeployMessage = `Failed to generate Initial Crawl List.
 Please check your permissions to the WordPress upload directory or check your
 Export Log in case of more info.`;

      adminPage.currentAction.innerHTML(failedDeployMessage);
      adminPage.pulsateCSS.style.display = 'none';
      adminPage.cancelExportButton.style.display = 'none';
      adminPage.resetDefaultSettingsButton.setAttribute("disabled", false);
      adminPage.saveSettingsButton.setAttribute("disabled", false);
      adminPage.startExportButton.setAttribute("disabled", true);
      adminPage.initialCrawlListLoader.style.display = 'none';
    }

    function prepareInitialFileList() {
      wp2staticGlobals.statusText = "Analyzing site... this may take a few minutes (but it's worth it!)";
      adminPage.currentAction.innerHTML(wp2staticGlobals.statusText);

      sendWP2StaticAJAX(
        "generate_filelist_preview",
        generateFileListSuccessCallback,
        generateFileListFailCallback,
      );
    }

    function sendWP2StaticAJAX(ajaxAction: string, successCallback: any, failCallback: any) {
      adminPage.hiddenActionField.value = "wp_static_html_output_ajax";
      adminPage.hiddenAJAXAction.value = ajaxAction;
      adminPage.progress.style.display = 'block';
      adminPage.pulsateCSS.style.display = 'block';

      /*
      const data = $(".options-form :input")
        .filter(
          (index, element) => {
            return $(element).val() !== "";
          },
        )
        .serialize();

      $.ajax(
        {
          data,
          dataType: "html",
          error: failCallback,
          method: "POST",
          success: successCallback,
          url: ajaxurl,
        },
      );
      */

      const data = new URLSearchParams(
        new FormData(".options-form")
      ).toString()

      let request = new XMLHttpRequest();
      request.open('POST', ajaxurl, true);
      request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      request.onload = successCallback;
      request.onerror = failCallback;
      request.send(data);
    }

    function saveOptionsSuccessCallback(serverResponse: any) {
      adminPage.progress.style.display = 'none';

      location.reload();
    }

    function saveOptionsFailCallback(serverResponse: any) {
      adminPage.progress.style.display = 'none';

      location.reload();
    }

    function saveOptions() {
      adminPage.currentAction.innerHTML("Saving options");
      sendWP2StaticAJAX(
        "save_options",
        saveOptionsSuccessCallback,
        saveOptionsFailCallback,
      );
    }


    function downloadExportLogSuccessCallback(serverResponse: any) {
      if (!serverResponse) {
        adminPage.currentAction.innerHTML(`Failed to download Export Log
 <a id="downloadExportLogButton" href="#">try again</a>`);
        adminPage.pulsateCSS.style.display = 'none';
      } else {
        adminPage.currentAction.innerHTML(`Download <a href="${serverResponse}">
 ${serverResponse}/a>`);
        adminPage.pulsateCSS.style.display = 'none';
      }
    }

    function downloadExportLogFailCallback(serverResponse: any) {
      adminPage.pulsateCSS.style.display = 'none';
      adminPage.currentAction.innerHTML(`Failed to download Export Log
 <a id="downloadExportLogButton" href="#">try again</a>`);
    }

    function deleteCrawlCacheSuccessCallback(serverResponse: any) {
      if (!serverResponse) {
        adminPage.pulsateCSS.style.display = 'none';
        adminPage.currentAction.innerHTML("Failed to delete Crawl Cache.");
      } else {
        adminPage.currentAction.innerHTML("Crawl Cache successfully deleted.");
        adminPage.pulsateCSS.style.display = 'none';
      }
    }

    function deleteCrawlCacheFailCallback(serverResponse: any) {
      adminPage.pulsateCSS.style.display = 'none';
      adminPage.currentAction.innerHTML("Failed to delete Crawl Cache.");
    }

    function downloadExportLog() {
      adminPage.currentAction.innerHTML("Downloading Export Log...");

      sendWP2StaticAJAX(
        "download_export_log",
        downloadExportLogSuccessCallback,
        downloadExportLogFailCallback,
      );
    }

    /*
    $(document).on(
      "click",
      "#detectEverythingButton",
      (evt) => {
        evt.preventDefault();
        $('#detectionOptionsTable input[type="checkbox"]').attr("checked", 1);
      },
    );
    */

    adminPage.detectEverythingButton.addEventListener(
      'click',
      (event) => {
        event.preventDefault();
        var inputs = adminPage.detectionOptionsInputs;

        for( var i = 0; i < inputs.length; i++ ) {
            inputs[i].setAttribute("checked", 1);   
        }
      }
    );

    adminPage.detectNothingButton.addEventListener(
      'click',
      (event) => {
        event.preventDefault();
        var inputs = adminPage.detectionOptionsInputs;

        for( var i = 0; i < inputs.length; i++ ) {
            inputs[i].setAttribute("checked", 0);   
        }
      }
    );

    adminPage.deleteCrawlCache.addEventListener(
      'click',
      (event) => {
        event.preventDefault();
        adminPage.currentAction.innerHTML("Deleting Crawl Cache...");

        sendWP2StaticAJAX(
          "delete_crawl_cache",
          deleteCrawlCacheSuccessCallback,
          deleteCrawlCacheFailCallback,
        );
      }
    );

    adminPage.downloadExportLogButton.addEventListener(
      'click',
      (event) => {
        event.preventDefault();
        downloadExportLog();
      }
    );

    function ajaxErrorHandler() {
      wp2staticGlobals.stopTimer();

      const failedDeployMessage = 'Failed during "' + wp2staticGlobals.statusText +
              '", <button id="downloadExportLogButton">Download export log</button>';

      adminPage.currentAction.innerHTML(failedDeployMessage);
      adminPage.pulsateCSS.style.display = 'none';
      adminPage.cancelExportButton.style.display = 'none';
      adminPage.resetDefaultSettingsButton.setAttribute("disabled", false);
      adminPage.saveSettingsButton.setAttribute("disabled", false);
      adminPage.startExportButton.setAttribute("disabled", false);
    }

    function startExportSuccessCallback(serverResponse: any) {
      const initialSteps = [
        "crawl_site",
        "post_process_archive_dir",
      ];

      wp2staticAJAX.doAJAXExport( initialSteps );
    }


    function startExport() {
      wp2staticGlobals.exportCommenceTime = +new Date();
      wp2staticGlobals.startTimer();

      validationErrors = getValidationErrors();

      if (validationErrors !== "") {
        alert(validationErrors);

        adminPage.progress.style.display = 'none';
        adminPage.cancelExportButton.style.display = 'none';
        adminPage.resetDefaultSettingsButton.setAttribute("disabled", false);
        adminPage.saveSettingsButton.setAttribute("disabled", false);
        adminPage.startExportButton.setAttribute("disabled", false);

        return false;
      }

      adminPage.currentAction.innerHTML("Starting export...");

      // reset export targets to avoid having left-overs from a failed run
      wp2staticGlobals.exportTargets = [];

      if (wp2staticGlobals.currentDeploymentMethod === "zip") {
        $("#createZip").attr("checked", "checked");
      }
      wp2staticGlobals.exportTargets.push(wp2staticGlobals.currentDeploymentMethod);

      sendWP2StaticAJAX(
        "prepare_for_export",
        startExportSuccessCallback,
        ajaxErrorHandler,
      );
    }

    function clearProgressAndResults() {
      $("#downloadZIP").hide();
      $("#goToMyStaticSite").hide();
      $("#exportDuration").hide();
    }

    function getValidationErrors() {
      // check for when targetFolder is showing (plugin reset state)
      if ($("#targetFolder").is(":visible") &&
            ($("#targetFolder").val() === "")) {
        validationErrors += "Target folder may not be empty. Please adjust your settings.";
      }

      if (($("#baseUrl").val() === undefined ||
            $("#baseUrl").val() === "") &&
            !$("#allowOfflineUsage").is(":checked")) {
        validationErrors += "Please set the Base URL field to the address you will host your static site.\n";
      }

      if (!isUrl(String($("#baseUrl").val())) && !$("#allowOfflineUsage").is(":checked")) {
        // TODO: testing / URL as base
        if ($("#baseUrl").val() !== "/") {
          validationErrors += "Please set the Base URL field to a valid URL, ie http://mystaticsite.com.\n";
        }
      }

      const requiredFields =
            wp2staticGlobals.deployOptions[wp2staticGlobals.currentDeploymentMethod].requiredFields;

      if (requiredFields) {
        validateEmptyFields(requiredFields);
      }

      const repoField = wp2staticGlobals.deployOptions[wp2staticGlobals.currentDeploymentMethod].repoField;

      if (repoField) {
        validateRepoField(repoField);
      }

      return validationErrors;
    }

    function validateRepoField(repoField: any) {
      const repo: string = String($("#" + repoField.field + "").val());

      if (repo !== "") {
        if (repo.split("/").length !== 2) {
          validationErrors += repoField.message;
        }
      }
    }

    function validateEmptyFields(requiredFields: any) {
      Object.keys(requiredFields).forEach(
        (key, index) => {
          if ($("#" + key).val() === "") {
            validationErrors += requiredFields[key] + "\n";
          }
        },
      );
    }

    function isUrl(url: string) {
      const match = url.match(protocolAndDomainRE);

      if (! match) {
        return false;
      }

      const everythingAfterProtocol = match[1];

      if (!everythingAfterProtocol) {
        return false;
      }

      if (localhostDomainRE.test(everythingAfterProtocol) ||
            nonLocalhostDomainRE.test(everythingAfterProtocol)) {
        return true;
      }

      return false;
    }

    function hideOtherVendorMessages() {
      const notices = $(".update-nag, .updated, .error, .is-dismissible, .elementor-message");

      $.each(
        notices,
        (index, element) => {
          if (!$(element).hasClass("wp2static-notice")) {
            $(element).hide();
          }
        },
      );
    }

    function setFormProcessor(fp: any) {
      if (fp in formProcessors) {

        const formProcessor: FormProcessor = formProcessors[fp];

        $("#form_processor_description").text(formProcessor.description);

        const website = formProcessor.website;

        const websiteLink: HTMLAnchorElement  = document.createElement("a");
        websiteLink.setAttribute("href", website);
        websiteLink.innerHTML = "Visit " + formProcessor.name;

        $("#form_processor_website").html(websiteLink);
        $("#form_processor_endpoint").attr("placeholder", formProcessor.placeholder);
      } else {
        $("#form_processor_description").text("");
        $("#form_processor_website").html("");
        $("#form_processor_endpoint").attr("placeholder", "Form endpoint");
      }
    }

    function populateFormProcessorOptions(fps: FormProcessor[]) {
      fps.forEach( (formProcessor) => {
        const opt = $("<option>").val(formProcessor.id).text(formProcessor.name);
        $("#form_processor_select").append(opt);
      });
    }

    /*
        TODO: quick win to get the select menu options to behave like the sendViaFTP, etc checkboxes
        */
    // TODO: remove this completely?
    function setDeploymentMethod(selectedDeploymentMethod: string) {
      // hide zip dl link for all
      $("#downloadZIP").hide();
      wp2staticGlobals.currentDeploymentMethod = selectedDeploymentMethod;

      // set the selected option in case calling this from outside the event handler
      $(".selected_deployment_method").val(selectedDeploymentMethod);
    }

    function offlineUsageChangeHandler(checkbox: HTMLElement) {
      if ($(checkbox).is(":checked")) {
        $("#baseUrl-zip").prop("disabled", true);
      } else {
        $("#baseUrl-zip").prop("disabled", false);
      }
    }

    function setExportSettingDetailsVisibility(changedCheckbox: HTMLElement) {
      const checkboxName = String($(changedCheckbox).attr("name"));
      const exportOptionName = checkboxName.replace("sendVia", "").toLowerCase();
      const exportOptionElements = $("." + exportOptionName);

      if ($(changedCheckbox).is(":checked")) {
        exportOptionElements.show();
        // unhide all the inputs, the following span and the following br
      } else {
        // hide all the inputs, the following span and the following br
        exportOptionElements.hide();
      }
    }

    /*
        render the information and settings blocks based on the deployment method selected
        */
    function renderSettingsBlock(selectedDeploymentMethod: string) {
      // hide non-active deployment methods
      $('[class$="_settings_block"]').hide();
      // hide those not selected
      $("." + selectedDeploymentMethod + "_settings_block").show();
    }

    function notifyMe() {
      if (!Notification) {
        alert("All exports are complete!.");
        return;
      }

      if (window.location.protocol === "https:") {
        if (Notification.permission !== "granted") {
          Notification.requestPermission();
        } else {
          const notification = new Notification(
            "WP Static HTML Export",
            {
              body: "Exports have finished!",
              icon: `https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/
Wordpress_Shiny_Icon.svg/768px-Wordpress_Shiny_Icon.svg.png`,
            },
          );

          notification.onclick = function() {
            parent.focus();
            window.focus();
            this.close();
          };
        }
      }
    }

    function loadLogFile() {
      // display loading icon
      $("#log_load_progress").show();

      $("#export_log_textarea").attr("disabled", 1);

      // set textarea content to 'Loading log file...'
      $("#export_log_textarea").html("Loading log file...");

      // load the log file
      $.get(
        logFileUrl + "?cacheBuster=" + Date.now(),
        (data) => {
          // hide loading icon
          $("#log_load_progress").hide();

          // set textarea to enabled
          $("#export_log_textarea").attr("disabled", 0);

          // set textarea content
          $("#export_log_textarea").html(data);
        },
      ).fail(
        () => {
          $("#log_load_progress").hide();

          // set textarea to enabled
          $("#export_log_textarea").attr("disabled", 0);

          // set textarea content
          $("#export_log_textarea").html("Requested log file not found");
        },
      );
    }

    if (Notification.permission !== "granted") {
      if (window.location.protocol === "https:") {
        Notification.requestPermission();
      }
    }

    $('input[type="checkbox"]').change(
      (event) => {
        setExportSettingDetailsVisibility(event.currentTarget);
      },
    );

    // disable zip base url field when offline usage is checked
    $("#allowOfflineUsage").change(
      (event) => {
        offlineUsageChangeHandler(event.currentTarget);
      },
    );

    // handler when form processor is changed
    $("#form_processor_select").change(
      (event) => {
        setFormProcessor((event.currentTarget as HTMLInputElement).value);
      },
    );

    // handler when deployment method is changed
    $(".selected_deployment_method").change(
      (event) => {
        renderSettingsBlock((event.currentTarget as HTMLInputElement).value);
        setDeploymentMethod((event.currentTarget as HTMLInputElement).value);
        clearProgressAndResults();
      },
    );

    // handler when log selector is changed
    $("#reload_log_button").click(
      () => {
        loadLogFile();
      },
    );

    function changeTab(targetTab: string) {
      const tabsContentMapping: any = {
        add_ons: "Add-ons",
        advanced_settings: "Advanced Options",
        automation_settings: "Automation",
        caching_settings: "Caching",
        crawl_settings: "Crawling",
        export_logs: "Logs",
        form_settings: "Forms",
        help_troubleshooting: "Help",
        processing_settings: "Processing",
        production_deploy: "Production",
        staging_deploy: "Staging",
        url_detection: "URL Detection",
        workflow_tab: "Workflow",
      };

      // switch the active tab
      $.each(
        $(".nav-tab"),
        (index, element) => {
          if ($(element).text() === targetTab) {
            $(element).addClass("nav-tab-active");
            $(element).blur();
          } else {
            $(element).removeClass("nav-tab-active");
          }
        },
      );

      // hide/show the tab content
      for (const key in tabsContentMapping) {
        if (tabsContentMapping.hasOwnProperty(key)) {
          if (tabsContentMapping[key] === targetTab) {
            $("." + key).show();
            $("html, body").scrollTop(0);
          } else {
            $("." + key).hide();
          }
        }
      }
    }

    $(document).on(
      "click",
      "#GoToDetectionTabButton",
      (evt) => {
        evt.preventDefault();
        changeTab("URL Detection");
      },
    );

    $(document).on(
      "click",
      "#GoToDeployTabButton,#GoToDeployTabLink",
      (evt) => {
        evt.preventDefault();
        changeTab("Deployment");
      },
    );

    // TODO: create action for #GenerateZIPOfflineUse
    // and #GenerateZIPDeployAnywhere

    $(document).on(
      "click",
      "#GoToAdvancedTabButton",
      (evt) => {
        evt.preventDefault();
        changeTab("Advanced Options");
      },
    );

    $(document).on(
      "click",
      ".nav-tab",
      (evt) => {
        evt.preventDefault();
        changeTab($(evt.currentTarget).text());
      },
    );

    $(document).on(
      "submit",
      "#general-options",
      (evt) => {
        evt.preventDefault();
      },
    );

    $(document).on(
      "click",
      "#send_supportRequest",
      (evt) => {
        evt.preventDefault();

        let supportRequest = $("#supportRequestContent").val();

        if ($("#supportRequestIncludeLog").is(":checked")) {
          $.get(
            logFileUrl,
            (data) => {
              supportRequest += "#### EXPORT LOG ###";
              supportRequest += data;

              data = {
                email: $("#supportRequestEmail").val(),
                supportRequest,
              };

              $.ajax(
                {
                  data,
                  dataType: "html",
                  error: sendSupportFailCallback,
                  method: "POST",
                  success: sendSupportSuccessCallback,
                  url: "https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/",
                },
              );
            },
          );
        }

        const postData = {
          email: $("#supportRequestEmail").val(),
          supportRequest,
        };

        $.ajax(
          {
            data: postData,
            dataType: "html",
            error: sendSupportFailCallback,
            method: "POST",
            success: sendSupportSuccessCallback,
            url: "https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/",
          },
        );
      },
    );

    $("#startExportButton").click(
      (event) => {
        clearProgressAndResults();
        $(event.currentTarget).prop("disabled", true);
        $(".saveSettingsButton").prop("disabled", true);
        $(".resetDefaultSettingsButton").prop("disabled", true);
        $(".cancelExportButton").show();
        startExport();
      },
    );

    $(".cancelExportButton").click(
      () => {
        const reallyCancel = confirm("Stop current export and reload page?");
        if (reallyCancel) {
          window.location.href = window.location.href;
        }
      },
    );

    function sendSupportSuccessCallback(serverResponse: any) {
      alert("Successful support request sent");
    }

    function sendSupportFailCallback(serverResponse: any) {
      alert("Failed to send support request. Please try again or contact help@wp2static.com.");
    }

    function resetDefaultSettingsSuccessCallback(serverResponse: any) {
      alert("Settings have been reset to default, the page will now be reloaded.");
      window.location.reload(true);
    }

    function resetDefaultSettingsFailCallback(serverResponse: any) {
      alert("Error encountered in trying to reset settings. Please try refreshing the page.");
    }

    $("#wp2static-footer").on(
      "click",
      ".resetDefaultSettingsButton",
      (event) => {
        event.preventDefault();

        sendWP2StaticAJAX(
          "reset_default_settings",
          resetDefaultSettingsSuccessCallback,
          resetDefaultSettingsFailCallback,
        );
      },
    );

    $("#wp2static-footer").on(
      "click",
      ".saveSettingsButton",
      (event) => {
        event.preventDefault();
        saveOptions();
      },
    );

    function deleteDeployCacheSuccessCallback(serverResponse: any) {
      if (serverResponse === "SUCCESS") {
        alert("Deploy cache cleared");
      } else {
        alert("FAIL: Unable to delete deploy cache");
      }

      spinner.hide();
      adminPage.pulsateCSS.style.display = 'none';
    }

    function deleteDeployCacheFailCallback(serverResponse: any) {
      alert("FAIL: Unable to delete deploy cache");

      spinner.hide();
      adminPage.pulsateCSS.style.display = 'none';
    }

    $(".wrap").on(
      "click",
      "#delete_deploy_cache_button",
      (event) => {
        event.preventDefault();
        const button = event.currentTarget;
        spinner = $(button).siblings("div.spinner");
        spinner.show();
        sendWP2StaticAJAX(
          "delete_deploy_cache",
          deleteDeployCacheSuccessCallback,
          deleteDeployCacheFailCallback,
        );
      },
    );

    function testDeploymentSuccessCallback(serverResponse: any) {
      if (serverResponse === "SUCCESS") {
        alert("Connection/Upload Test Successful");
      } else {
        alert("FAIL: Unable to complete test upload to " + wp2staticGlobals.currentDeploymentMethod);
      }

      spinner.hide();
      adminPage.pulsateCSS.style.display = 'none';
    }

    function testDeploymentFailCallback(serverResponse: any) {
      alert("FAIL: Unable to complete test upload to " + wp2staticGlobals.currentDeploymentMethod);
      spinner.hide();
      adminPage.pulsateCSS.style.display = 'none';
    }

    $(".wrap").on(
      "click",
      '[id$="-test-button"]',
      (event) => {
        event.preventDefault();
        spinner = $("button").siblings("div.spinner");
        spinner.show();

        sendWP2StaticAJAX(
          "test_" + wp2staticGlobals.currentDeploymentMethod,
          testDeploymentSuccessCallback,
          testDeploymentFailCallback,
        );
      },
    );

    $(".wrap").on(
      "click",
      "#save-and-reload",
      (event) => {
        event.preventDefault();
        saveOptions();
      },
    );

    $(".spinner").hide();

    // guard against selected option for add-on not currently activated
    if ($("#baseUrl-" + wp2staticGlobals.currentDeploymentMethod).val() === undefined) {
      wp2staticGlobals.currentDeploymentMethod = "folder";
    }

    populateFormProcessorOptions(formProcessors);

    setFormProcessor(selectedFormProcessor);

    // call change handler on page load, to set correct state
    const offlineUsageCheckbox: any = document.getElementById("#allowOfflineUsage");
    if ( offlineUsageCheckbox ) {
      offlineUsageChangeHandler(offlineUsageCheckbox);
    }

    // set and show the previous selected deployment method
    renderSettingsBlock(wp2staticGlobals.currentDeploymentMethod);

    // set the select to the current deployment type
    setDeploymentMethod(wp2staticGlobals.currentDeploymentMethod);

    // hide all but WP2Static messages
    hideOtherVendorMessages();

    prepareInitialFileList();
  },
);
