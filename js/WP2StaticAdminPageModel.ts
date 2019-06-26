export class WP2StaticAdminPageModel {

  public allowOfflineUsage: HTMLElement;
  public baseUrl: HTMLElement;
  public baseUrlZip: HTMLElement;
  public cancelExportButton: HTMLElement;
  public createZip: HTMLElement;
  public currentAction: HTMLElement;
  public deleteCrawlCache: HTMLInputElement;
  public detectEverythingButton: HTMLInputElement;
  public detectNothingButton: HTMLInputElement;
  public detectionOptionsInputs: <Array>HTMLInputElement;
  public downloadExportLogButton: HTMLInputElement;
  public downloadZIP: HTMLElement;
  public exportDuration: HTMLElement;
  public formProcessorDescription: HTMLElement;
  public formProcessorEndpoint: HTMLElement;
  public formProcessorSelect: HTMLElement;
  public formProcessorWebsite: HTMLElement;
  public generalOptions: HTMLElement;
  public goToAdvancedTabButton: HTMLElement;
  public goToDeployTabButton: HTMLElement;
  public goToDeployTabLink: HTMLElement;
  public goToDetectionTabButton: HTMLElement;
  public goToMyStaticSite: HTMLElement;
  public hiddenAJAXAction: HTMLInputElement;
  public hiddenActionField: HTMLInputElement;
  public initialCrawlListCount: HTMLElement;
  public initialCrawlListLoader: HTMLElement;
  public navigationTabs: <Array>HTMLElement;
  public previewInitialCrawlListButton: HTMLElement;
  public progress: HTMLElement;
  public pulsateCSS: HTMLElement;
  public resetDefaultSettingsButton: HTMLElement;
  public saveSettingsButton: HTMLElement;
  public saveAndReloadButton: HTMLElement;
  public selectedDeploymentMethod: HTMLElement;
  public sendSupportRequestButton: HTMLElement;
  public sendSupportRequestContent: HTMLElement;
  public sendSupportRequestIncludeLog: HTMLElement;
  public settingsBlocks: <Array>HTMLElement;
  public startExportButton: HTMLElement;
  public supportRequestEmail: HTMLElement;
  public targetFolder: HTMLElement;
  public vendorNotices: <Array>HTMLElement;

  constructor() {
    this.allowOfflineUsage = document.getElementById("allowOfflineUsage");
    this.baseUrl = document.getElementById("baseUrl");
    this.baseUrlZip = document.getElementById("baseUrl-zip");
    this.cancelExportButton = document.getElementByClass("cancelExportButton");
    this.createZip = document.getElementById("createZip");
    this.currentAction = document.getElementById("current_action");
    this.deleteCrawlCache = document.getElementById("deleteCrawlCache");
    this.detectEverythingButton = document.getElementById("detectEverythingButton");
    this.detectNothingButton = document.getElementById("detectNothingButton");
    this.detectionOptionsInputs = document.querySelectorAll('#detectionOptionsTable input[type="checkbox"]');
    this.downloadExportLogButton = document.getElementById("downloadExportLogButton");
    this.downloadZIP = document.getElementById("downloadZIP");
    this.exportDuration = document.getElementById("exportDuration");
    this.formProcessorDescription = document.getElementById("form_processor_description");
    this.formProcessorEndpoint = document.getElementById("form_processor_endpoint");
    this.formProcessorSelect = document.getElementById("form_processor_select");
    this.formProcessorWebsite = document.getElementById("form_processor_website");
    this.generalOptions = document.getElementById("general-options");
    this.goToAdvancedTabButton = document.getElementById("GoToAdvancedTabButton");
    this.goToDeployTabButton = document.getElementById("GoToDeployTabButton");
    this.goToDeployTabLink = document.getElementById("GoToDeployTabLink");
    this.goToDetectionTabButton = document.getElementById("GoToDetectionTabButton");
    this.goToMyStaticSite = document.getElementById("goToMyStaticSite");
    this.hiddenAJAXAction = document.getElementById("hiddenAJAXAction");
    this.hiddenActionField = document.getElementByClass("hiddenActionField");
    this.initialCrawlListCount = document.getElementById("initial_crawl_list_count");
    this.initialCrawlListLoader = document.getElementById("initial_crawl_list_loader");
    this.navigationTabs = document.querySelectorAll(".nav-tab");
    this.previewInitialCrawlListButton = document.getElementById("preview_initial_crawl_list_button");
    this.progress = document.getElementById("progress");
    this.pulsateCSS = document.getElementByClass("pulsate-css");
    this.resetDefaultSettingsButton = document.getElementByClass("resetDefaultSettingsButton");
    this.saveSettingsButton = document.getElementByClass("saveSettingsButton");
    this.saveAndReloadButton = document.getElementByClass("save-and-reload");
    this.selectedDeploymentMethod = document.getElementByClass("selected_deployment_method");
    this.sendSupportRequestButton = document.getElementById("send_supportRequest");
    this.sendSupportRequestContent = document.getElementById("supportRequestContent");
    this.sendSupportRequestEmail = document.getElementById("supportRequestEmail");
    this.sendSupportRequestIncludeLog = document.getElementById("supportRequestIncludeLog");
    this.settingsBlocks = document.querySelectorAll('[class$="_settings_block"]');
    this.startExportButton = document.getElementById("startExportButton");
    this.targetFolder = document.getElementById("targetFolder");
    this.vendorNotices =
      document.querySelectorAll(".update-nag, .updated, .error, .is-dismissible, .elementor-message");

  }
}

