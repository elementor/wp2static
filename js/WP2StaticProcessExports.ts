import $ from "jquery";

import { WP2StaticAJAX } from "./WP2StaticAJAX";

const wp2staticAJAX = new WP2StaticAJAX();

export class WP2StaticProcessExports {

  processExportTargets(
    statusDescriptions,
    exportTargets,
    deployOptions,
    currentDeploymentMethod,
    siteInfo
  ) {

    if (exportTargets.length > 0) {
      const target = exportTargets.shift();
      const exportSteps = deployOptions[target].exportSteps;

      wp2staticAJAX.doAJAXExport(
        exportSteps,
        statusDescriptions,
        exportTargets,
        deployOptions,
        currentDeploymentMethod,
        siteInfo
      );
    } else {
      // if zip was selected, call to get zip name and enable the button with the link to download
      if (currentDeploymentMethod === "zip") {
        const zipURL = siteInfo.uploads_url + "wp2static-exported-site.zip?cacheBuster=" + Date.now();
        $("#downloadZIP").attr("href", zipURL);
        $("#downloadZIP").show();
      } else {
        // for other methods, show the Go to my static site link
        const baseUrl = String($("#baseUrl").val());
        $("#goToMyStaticSite").attr("href", baseUrl);
        $("#goToMyStaticSite").show();
      }

      // all complete
      // const exportCompleteTime: number = +new Date();
      // const exportDuration = exportCompleteTime - exportCommenceTime;

      // clear export commence time for next run
      // exportCommenceTime = 0;

      // stopTimer();
      // $("#current_action").text(`Process completed in
//${millisToMinutesAndSeconds(exportDuration)} (mins:ss)`);
      $("#goToMyStaticSite").focus();
      $(".pulsate-css").hide();
      $("#startExportButton").prop("disabled", false);
      $(".saveSettingsButton").prop("disabled", false);
      $(".resetDefaultSettingsButton").prop("disabled", false);
      $(".cancelExportButton").hide();
      // notifyMe();
    }
  }
}
