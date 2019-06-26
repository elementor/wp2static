export class WP2StaticAdminPageModel {

  cancelExportButton: HTMLElement;
  currentAction: HTMLElement;
  deleteCrawlCache: HTMLInputElement;
  detectEverythingButton: HTMLInputElement;
  detectNothingButton: HTMLInputElement;
  detectionOptionsInputs: <Array>HTMLInputElement;
  downloadExportLogButton: HTMLInputElement;
  hiddenAJAXAction: HTMLInputElement;
  hiddenActionField: HTMLInputElement;
  initialCrawlListCount: HTMLElement;
  initialCrawlListLoader: HTMLElement;
  previewInitialCrawlListButton: HTMLElement;
  progress: HTMLElement;
  pulsateCSS: HTMLElement;
  resetDefaultSettingsButton: HTMLElement;
  saveSettingsButton: HTMLElement;
  startExportButton: HTMLElement;

  constructor() {
    this.cancelExportButton = document.getElementByClass('cancelExportButton');
    this.currentAction = document.getElementById('current_action');
    this.deleteCrawlCache = document.getElementById('deleteCrawlCache');
    this.detectEverythingButton = document.getElementById('detectEverythingButton');
    this.detectNothingButton = document.getElementById('detectNothingButton');
    this.detectionOptionsInputs = document.querySelectorAll('#detectionOptionsTable input[type="checkbox"]');
    this.downloadExportLogButton = document.getElementById('downloadExportLogButton');
    this.hiddenAJAXAction = document.getElementById('hiddenAJAXAction');
    this.hiddenActionField = document.getElementByClass('hiddenActionField');
    this.initialCrawlListCount = document.getElementById('initial_crawl_list_count');
    this.initialCrawlListLoader = document.getElementById('initial_crawl_list_loader');
    this.previewInitialCrawlListButton = document.getElementById('preview_initial_crawl_list_button');
    this.progress = document.getElementById('progress');
    this.pulsateCSS = document.getElementByClass('pulsate-css');
    this.resetDefaultSettingsButton = document.getElementByClass('resetDefaultSettingsButton');
    this.saveSettingsButton = document.getElementByClass('saveSettingsButton');
    this.startExportButton = document.getElementById('startExportButton');

  } 
}

