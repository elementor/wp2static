import { WP2StaticAJAX } from "./WP2StaticAJAX";
import { WP2StaticGlobals } from "./WP2StaticGlobals";

export class WP2StaticProcessExports {

  public wp2staticGlobals: WP2StaticGlobals;

  constructor( wp2staticGlobals: WP2StaticGlobals ) {
      this.wp2staticGlobals = wp2staticGlobals;
  }

  public processExportTargets() {

    const wp2staticAJAX = new WP2StaticAJAX( this.wp2staticGlobals );
    const adminPage = this.wp2staticGlobals.adminPage;

    if (this.wp2staticGlobals.exportTargets.length > 0) {
      const target: string = String(this.wp2staticGlobals.exportTargets.shift());


      const exportSteps = this.wp2staticGlobals.deployOptions[target].exportSteps;

      wp2staticAJAX.doAJAXExport( exportSteps );
    } else {
      // if zip was selected, call to get zip name and enable the button with the link to download
      if (this.wp2staticGlobals.currentDeploymentMethod === "zip") {
        const zipURL = `${this.wp2staticGlobals.siteInfo.uploads_url}
wp2static-exported-site.zip?cacheBuster=${Date.now()}`;
        adminPage.downloadZIP.setAttribute("href", zipURL);
        adminPage.downloadZIP.style.display = "inline";
      } else {
        // for other methods, show the Go to my static site link
        const baseUrl = String(adminPage.baseUrl.value);
        adminPage.goToMyStaticSite.setAttribute("href", baseUrl);
        adminPage.goToMyStaticSite.style.display = "inline";
      }

      // all complete
      this.wp2staticGlobals.exportCompleteTime = +new Date();
      this.wp2staticGlobals.exportDuration =
        this.wp2staticGlobals.exportCompleteTime - this.wp2staticGlobals.exportCommenceTime;

      // clear export commence time for next run
      this.wp2staticGlobals.exportCommenceTime = 0;

      adminPage.currentAction.textContent = `Process completed in
${this.wp2staticGlobals.millisToMinutesAndSeconds(this.wp2staticGlobals.exportDuration)} (mins:ss)`;

      adminPage.cancelExportButton.style.display = "none";
      adminPage.pulsateCSS.style.display = "none";
      adminPage.resetDefaultSettingsButton.removeAttribute("disabled");
      adminPage.saveSettingsButton.removeAttribute("disabled");
      adminPage.startExportButton.removeAttribute("disabled");
      adminPage.goToMyStaticSite.focus();

      // TODO: reconnect notifyMe();
    }
  }
}
