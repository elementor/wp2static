import $ from "jquery";

export class WP2StaticGlobals {

  aProperty: string = 'default value'; 
  exportCommenceTime: number = 0;
  exportCompleteTime: number = 0;
  timerIntervalID: number = 0;
  exportDuration: number = 0;

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

  millisToMinutesAndSeconds( millis ) {
    const minutes = Math.floor(millis / 60000);
    const seconds: number = parseFloat( ((millis % 60000) / 1000).toFixed(0) );

    return minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
  }

}
