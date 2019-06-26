export class WP2StaticAdminPageModel {

  public allowOfflineUsage: HTMLElement | null;
  public baseUrl: HTMLElement | null;
  public baseUrlZip: HTMLElement | null;
  public cancelExportButton: HTMLElement | null;
  public createZip: HTMLElement | null;
  public currentAction: HTMLElement | null;
  public deleteCrawlCache: HTMLInputElement | null;
  public detectEverythingButton: HTMLInputElement | null;
  public detectNothingButton: HTMLInputElement | null;
  public detectionOptionsInputs: HTMLInputElement[] | null;
  public downloadExportLogButton: HTMLInputElement | null;
  public downloadZIP: HTMLElement | null;
  public exportDuration: HTMLElement | null;
  public formProcessorDescription: HTMLElement | null;
  public formProcessorEndpoint: HTMLElement | null;
  public formProcessorSelect: HTMLElement | null;
  public formProcessorWebsite: HTMLElement | null;
  public generalOptions: HTMLElement | null;
  public goToAdvancedTabButton: HTMLElement | null;
  public goToDeployTabButton: HTMLElement | null;
  public goToDeployTabLink: HTMLElement | null;
  public goToDetectionTabButton: HTMLElement | null;
  public goToMyStaticSite: HTMLElement | null;
  public hiddenAJAXAction: HTMLInputElement | null;
  public hiddenActionField: HTMLInputElement | null;
  public initialCrawlListCount: HTMLElement | null;
  public initialCrawlListLoader: HTMLElement | null;
  public navigationTabs: HTMLElement[] | null;
  public previewInitialCrawlListButton: HTMLElement | null;
  public progress: HTMLElement | null;
  public pulsateCSS: HTMLElement | null;
  public resetDefaultSettingsButton: HTMLElement | null;
  public saveSettingsButton: HTMLElement | null;
  public selectedDeploymentMethod: HTMLElement | null;
  public sendSupportRequestButton: HTMLElement | null;
  public sendSupportRequestContent: HTMLElement | null;
  public sendSupportRequestEmail: HTMLElement | null;
  public sendSupportRequestIncludeLog: HTMLElement | null;
  public settingsBlocks: HTMLElement[] | null;
  public startExportButton: HTMLElement | null;
  public targetFolder: HTMLElement | null;
  public vendorNotices: HTMLElement[] | null;

  constructor() {
    this.allowOfflineUsage = document.getElementById("allowOfflineUsage");
    this.baseUrl = document.getElementById("baseUrl");
    this.baseUrlZip = document.getElementById("baseUrl-zip");
    this.cancelExportButton = document.getElementById("cancelExportButton");
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
    this.hiddenActionField = document.getElementById("hiddenActionField");
    this.initialCrawlListCount = document.getElementById("initial_crawl_list_count");
    this.initialCrawlListLoader = document.getElementById("initial_crawl_list_loader");
    this.navigationTabs = document.querySelectorAll(".nav-tab");
    this.previewInitialCrawlListButton = document.getElementById("preview_initial_crawl_list_button");
    this.progress = document.getElementById("progress");
    this.pulsateCSS = document.getElementById("pulsate-css");
    this.resetDefaultSettingsButton = document.getElementById("resetDefaultSettingsButton");
    this.saveSettingsButton = document.getElementById("saveSettingsButton");
    this.selectedDeploymentMethod = document.getElementById("selected_deployment_method");
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

