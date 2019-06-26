export class WP2StaticGlobals {

  aProperty: string = 'default value'; 
  exportCommenceTime: number = 0;
  exportCompleteTime: number = 0;
  timerIntervalID: number = 0;
  exportDuration: number = 0;
  statusDescriptions: any = {
    crawl_site: "Crawling initial file list",
    post_export_teardown: "Cleaning up after processing",
    post_process_archive_dir: "Processing the crawled files",
  };
  currentDeploymentMethod: string = '';
  siteInfo: any;
  exportTargets: Array<string> = [];
  deployOptions: any = {
    folder: {
      exportSteps: [
        "finalize_deployment",
      ],
      requiredFields: {
      },
    },
    zip: {
      exportSteps: [
        "finalize_deployment",
      ],
      requiredFields: {
      },
    },
  };
  statusText: string = "";

  getAll () {
    return { something : this.aProperty }
  }

  changeProperty ( newProp: string ) {
    this.aProperty = newProp; 
  }

  startTimer() {
    this.timerIntervalID = window.setInterval(this.updateTimer, 1000);
  }

  stopTimer() {
    window.clearInterval(this.timerIntervalID);
  }

  updateTimer() {
    this.exportCompleteTime = +new Date();
    const runningTime = this.exportCompleteTime - this.exportCommenceTime;

    $("#export_timer").html(
      "<b>Export duration: </b>" + this.millisToMinutesAndSeconds(runningTime)
    );
  }

  millisToMinutesAndSeconds( millis: number ) {
    const minutes = Math.floor(millis / 60000);
    const seconds: number = parseFloat( ((millis % 60000) / 1000).toFixed(0) );

    return minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
  }

}
