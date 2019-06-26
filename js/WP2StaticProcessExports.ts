import $ from "jquery";

import { WP2StaticAJAX } from "./WP2StaticAJAX";
import { WP2StaticGlobals } from "./WP2StaticGlobals";

export class WP2StaticProcessExports {
  
  wp2staticGlobals: WP2StaticGlobals;

  constructor( wp2staticGlobals: WP2StaticGlobals ) {
      this.wp2staticGlobals = wp2staticGlobals;
  }

  processExportTargets(
    statusDescriptions,
    exportTargets,
    deployOptions,
    currentDeploymentMethod,
    siteInfo
  ) {

    const wp2staticAJAX = new WP2StaticAJAX( this.wp2staticGlobals );

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
      this.wp2staticGlobals.exportCompleteTime = +new Date();
      this.wp2staticGlobals.exportDuration =
        this.wp2staticGlobals.exportCompleteTime - this.wp2staticGlobals.exportCommenceTime;

      // clear export commence time for next run
      this.wp2staticGlobals.exportCommenceTime = 0;

      this.wp2staticGlobals.stopTimer();

      $("#current_action").text(`Process completed in
${this.wp2staticGlobals.millisToMinutesAndSeconds(this.wp2staticGlobals.exportDuration)} (mins:ss)`);

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
