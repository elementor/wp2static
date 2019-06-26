export class WP2StaticAdminPageModel {

  public allowOfflineUsage: HTMLElement;
  public baseUrl: HTMLElement;
  public baseUrlZip: HTMLElement;
  public cancelExportButton: HTMLElement;
  public createZip: HTMLElement;
  public currentAction: HTMLElement;
  public deleteCrawlCache: HTMLElement;
  public detectEverythingButton: HTMLElement;
  public detectNothingButton: HTMLElement;
  public detectionOptionsInputs: NodeListOf<Element>;
  public downloadExportLogButton: HTMLElement;
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
  public hiddenAJAXAction: HTMLElement;
  public hiddenActionField: HTMLElement;
  public initialCrawlListCount: HTMLElement;
  public initialCrawlListLoader: HTMLElement;
  public navigationTabs: NodeListOf<Element>;
  public previewInitialCrawlListButton: HTMLElement;
  public progress: HTMLElement;
  public pulsateCSS: HTMLElement;
  public resetDefaultSettingsButton: HTMLElement;
  public saveSettingsButton: HTMLElement;
  public selectedDeploymentMethod: HTMLElement;
  public sendSupportRequestButton: HTMLElement;
  public sendSupportRequestContent: HTMLElement;
  public sendSupportRequestEmail: HTMLElement;
  public sendSupportRequestIncludeLog: HTMLElement;
  public settingsBlocks: NodeListOf<Element>;
  public startExportButton: HTMLElement;
  public targetFolder: HTMLElement;
  public vendorNotices: NodeListOf<Element>;

  constructor() {
    this.allowOfflineUsage = document.getElementById("allowOfflineUsage")!;
    this.baseUrl = document.getElementById("baseUrl")!;
    this.baseUrlZip = document.getElementById("baseUrl-zip")!;
    this.cancelExportButton = document.getElementById("cancelExportButton")!;
    this.createZip = document.getElementById("createZip")!;
    this.currentAction = document.getElementById("current_action")!;
    this.deleteCrawlCache = document.getElementById("deleteCrawlCache")!;
    this.detectEverythingButton = document.getElementById("detectEverythingButton")!;
    this.detectNothingButton = document.getElementById("detectNothingButton")!;
    this.detectionOptionsInputs = document.querySelectorAll('#detectionOptionsTable input[type="checkbox"]')!;
    this.downloadExportLogButton = document.getElementById("downloadExportLogButton")!;
    this.downloadZIP = document.getElementById("downloadZIP")!;
    this.exportDuration = document.getElementById("exportDuration")!;
    this.formProcessorDescription = document.getElementById("form_processor_description")!;
    this.formProcessorEndpoint = document.getElementById("form_processor_endpoint")!;
    this.formProcessorSelect = document.getElementById("form_processor_select")!;
    this.formProcessorWebsite = document.getElementById("form_processor_website")!;
    this.generalOptions = document.getElementById("general-options")!;
    this.goToAdvancedTabButton = document.getElementById("GoToAdvancedTabButton")!;
    this.goToDeployTabButton = document.getElementById("GoToDeployTabButton")!;
    this.goToDeployTabLink = document.getElementById("GoToDeployTabLink")!;
    this.goToDetectionTabButton = document.getElementById("GoToDetectionTabButton")!;
    this.goToMyStaticSite = document.getElementById("goToMyStaticSite")!;
    this.hiddenAJAXAction = document.getElementById("hiddenAJAXAction")!;
    this.hiddenActionField = document.getElementById("hiddenActionField")!;
    this.initialCrawlListCount = document.getElementById("initial_crawl_list_count")!;
    this.initialCrawlListLoader = document.getElementById("initial_crawl_list_loader")!;
    this.navigationTabs = document.querySelectorAll(".nav-tab")!;
    this.previewInitialCrawlListButton = document.getElementById("preview_initial_crawl_list_button")!;
    this.progress = document.getElementById("progress")!;
    this.pulsateCSS = document.getElementById("pulsate-css")!;
    this.resetDefaultSettingsButton = document.getElementById("resetDefaultSettingsButton")!;
    this.saveSettingsButton = document.getElementById("saveSettingsButton")!;
    this.selectedDeploymentMethod = document.getElementById("selected_deployment_method")!;
    this.sendSupportRequestButton = document.getElementById("send_supportRequest")!;
    this.sendSupportRequestContent = document.getElementById("supportRequestContent")!;
    this.sendSupportRequestEmail = document.getElementById("supportRequestEmail")!;
    this.sendSupportRequestIncludeLog = document.getElementById("supportRequestIncludeLog")!;
    this.settingsBlocks = document.querySelectorAll('[id="_settings_block"]')!;
    this.startExportButton = document.getElementById("startExportButton")!;
    this.targetFolder = document.getElementById("targetFolder")!;
    this.vendorNotices =
      document.querySelectorAll(".update-nag, .updated, .error, .is-dismissible, .elementor-message")!;

  }
}

