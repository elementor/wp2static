declare var ajaxurl: string;

import { WP2StaticAdminPageModel } from "./WP2StaticAdminPageModel";
import { WP2StaticGlobals } from "./WP2StaticGlobals";
import { WP2StaticProcessExports } from "./WP2StaticProcessExports";

export class WP2StaticAJAX {

    public wp2staticGlobals: WP2StaticGlobals;
    public wp2staticProcessExports: WP2StaticProcessExports;
    public adminPage: WP2StaticAdminPageModel;

    constructor( wp2staticGlobals: WP2StaticGlobals ) {
      this.wp2staticGlobals = wp2staticGlobals;
      this.wp2staticProcessExports =
        new WP2StaticProcessExports( this.wp2staticGlobals );

      this.adminPage = this.wp2staticGlobals.adminPage;
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
    public doAJAXExport(
        args: any,
    ) {
        const exportAction = args[0];

        this.wp2staticGlobals.statusText = exportAction;

        if (this.wp2staticGlobals.statusDescriptions[exportAction] !== undefined) {
          this.wp2staticGlobals.statusText = this.wp2staticGlobals.statusDescriptions[exportAction];
        } else {
          this.wp2staticGlobals.statusText = exportAction;
        }

        this.adminPage.currentAction.innerHTML = this.wp2staticGlobals.statusText;

        this.adminPage.hiddenActionField.value = "wp_static_html_output_ajax";
        this.adminPage.hiddenAJAXAction.value = exportAction;

        const data = new URLSearchParams(
        // https://github.com/Microsoft/TypeScript/issues/30584
        // @ts-ignore
          new FormData(this.adminPage.optionsForm),
        ).toString();

        const request = new XMLHttpRequest();
        request.open("POST", ajaxurl, true);
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        request.onload = (event: any) => {
          const serverResponse: string | number = event.target.response;

          // if an action is successful, and there are other actions queued up
          if (serverResponse === "SUCCESS" && args.length > 1) {
            // rm first action now that it's succeeded
            args.shift();
            // call function with all other actions
            this.doAJAXExport(args);
            // if an action is in progress incremental, it will call itself again
          } else if (serverResponse > 0) {
            this.doAJAXExport(args);
          } else if (serverResponse === "SUCCESS") {
            // not an incremental action, continue on with export targets
            this.wp2staticProcessExports.processExportTargets();
          } else {
            this.ajaxErrorHandler();
          }
        };
        request.onerror = this.ajaxErrorHandler;
        request.send(data);
    }

    public ajaxErrorHandler() {
      this.wp2staticGlobals.stopTimer();

      const failedDeployMessage = 'Failed during "' + this.wp2staticGlobals.statusText +
              '", <button id="downloadExportLogButton">Download export log</button>';

      this.adminPage.cancelExportButton.style.display = "none";
      this.adminPage.currentAction.innerHTML = failedDeployMessage;
      this.adminPage.pulsateCSS.style.display = "none";
      this.adminPage.resetDefaultSettingsButton.removeAttribute("disabled");
      this.adminPage.saveSettingsButton.removeAttribute("disabled");
      this.adminPage.startExportButton.removeAttribute("disabled");
    }
}
