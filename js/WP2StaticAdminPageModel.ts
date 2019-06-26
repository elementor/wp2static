export class WP2StaticAdminPageModel {

  currentAction: HTMLElement;
  pulsateCSS: HTMLElement;
  progress: HTMLElement;
  initialCrawlListLoader: HTMLElement;
  initialCrawlListCount: HTMLElement;
  previewInitialCrawlListButton: HTMLElement;
  startExportButton: HTMLElement;
  saveSettingsButton: HTMLElement;
  resetDefaultSettingsButton: HTMLElement;
  cancelExportButton: HTMLElement;

  constructor() {
    this.currentAction = document.getElementById('current_action');
    this.progress = document.getElementById('progress');
    this.pulsateCSS = document.getElementByClass('pulsate-css');
    this.initialCrawlListLoader = document.getElementById('initial_crawl_list_loader');
    this.initialCrawlListCount = document.getElementById('initial_crawl_list_count');
    this.previewInitialCrawlListButton = document.getElementById('preview_initial_crawl_list_button');
    this.startExportButton = document.getElementById('startExportButton');
    this.saveSettingsButton = document.getElementByClass('saveSettingsButton');
    this.resetDefaultSettingsButton = document.getElementByClass('resetDefaultSettingsButton');
    this.cancelExportButton = document.getElementByClass('cancelExportButton');

  } 
}

