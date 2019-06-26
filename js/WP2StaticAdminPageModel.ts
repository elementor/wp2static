export class WP2StaticAdminPageModel {

  public allowOfflineUsage: HTMLInputElement;
  public baseUrl: HTMLInputElement;
  public baseUrlZip: HTMLInputElement;
  public cancelExportButton: HTMLElement;
  public createZip: HTMLElement;
  public currentAction: HTMLElement;
  public deleteCrawlCache: HTMLElement;
  public deleteDeployCache: HTMLElement;
  public detectEverythingButton: HTMLElement;
  public detectNothingButton: HTMLElement;
  public detectionOptionsInputs: NodeListOf<Element>;
  public downloadZIP: HTMLElement;
  public exportDuration: HTMLElement;
  public formProcessorDescription: HTMLInputElement;
  public formProcessorEndpoint: HTMLInputElement;
  public formProcessorSelect: HTMLSelectElement;
  public formProcessorWebsite: HTMLInputElement;
  public generalOptions: HTMLElement;
  public goToDeployTabButton: HTMLElement;
  public goToMyStaticSite: HTMLElement;
  public hiddenAJAXAction: HTMLInputElement;
  public hiddenActionField: HTMLInputElement;
  public initialCrawlListCount: HTMLElement;
  public initialCrawlListLoader: HTMLElement;
  public navigationTabs: NodeListOf<Element>;
  public optionsForm: HTMLFormElement;
  public previewInitialCrawlListButton: HTMLElement;
  public progress: HTMLElement;
  public pulsateCSS: HTMLElement;
  public resetDefaultSettingsButton: HTMLElement;
  public saveSettingsButton: HTMLElement;
  public selectedDeploymentMethod: HTMLSelectElement;
  public sendSupportRequestButton: HTMLElement;
  public sendSupportRequestContent: HTMLInputElement;
  public sendSupportRequestEmail: HTMLInputElement;
  public sendSupportRequestIncludeLog: HTMLInputElement;
  public settingsBlocks: NodeListOf<Element>;
  public startExportButton: HTMLElement;
  public targetFolder: HTMLInputElement;
  public vendorNotices: NodeListOf<Element>;

  constructor() {
    this.allowOfflineUsage = <HTMLInputElement>document.getElementById("allowOfflineUsage")!;
    this.baseUrl = <HTMLInputElement>document.getElementById("baseUrl")!;
    this.baseUrlZip = <HTMLInputElement>document.getElementById("baseUrl-zip")!;
    this.cancelExportButton = document.getElementById("cancelExportButton")!;
    this.createZip = document.getElementById("createZip")!;
    this.currentAction = document.getElementById("current_action")!;
    this.deleteCrawlCache = document.getElementById("deleteCrawlCache")!;
    this.deleteDeployCache = document.getElementById("delete_deploy_cache_button")!;
    this.detectEverythingButton = document.getElementById("detectEverythingButton")!;
    this.detectNothingButton = document.getElementById("detectNothingButton")!;
    this.detectionOptionsInputs = document.querySelectorAll('#detectionOptionsTable input[type="checkbox"]')!;
    this.downloadZIP = document.getElementById("downloadZIP")!;
    this.exportDuration = document.getElementById("exportDuration")!;
    this.formProcessorDescription = <HTMLInputElement>document.getElementById("form_processor_description")!;
    this.formProcessorEndpoint = <HTMLInputElement>document.getElementById("form_processor_endpoint")!;
    this.formProcessorSelect = <HTMLSelectElement>document.getElementById("form_processor_select")!;
    this.formProcessorWebsite = <HTMLInputElement>document.getElementById("form_processor_website")!;
    this.generalOptions = document.getElementById("general-options")!;
    this.goToDeployTabButton = document.getElementById("GoToDeployTabButton")!;
    this.goToMyStaticSite = document.getElementById("goToMyStaticSite")!;
    this.hiddenAJAXAction = <HTMLInputElement>document.getElementById("hiddenAJAXAction")!;
    this.hiddenActionField = <HTMLInputElement>document.getElementById("hiddenActionField")!;
    this.initialCrawlListCount = document.getElementById("initial_crawl_list_count")!;
    this.initialCrawlListLoader = document.getElementById("initial_crawl_list_loader")!;
    this.navigationTabs = document.querySelectorAll(".nav-tab")!;
    this.optionsForm = <HTMLFormElement>document.getElementById("general-options")!;
    this.previewInitialCrawlListButton = document.getElementById("preview_initial_crawl_list_button")!;
    this.progress = document.getElementById("progress")!;
    this.pulsateCSS = document.getElementById("pulsate-css")!;
    this.resetDefaultSettingsButton = document.getElementById("resetDefaultSettingsButton")!;
    this.saveSettingsButton = document.getElementById("saveSettingsButton")!;
    this.selectedDeploymentMethod = <HTMLSelectElement>document.getElementById("selected_deployment_method")!;
    this.sendSupportRequestButton = <HTMLInputElement>document.getElementById("send_support_request")!;
    this.sendSupportRequestContent = <HTMLInputElement>document.getElementById("supportRequestContent")!;
    this.sendSupportRequestEmail = <HTMLInputElement>document.getElementById("supportRequestEmail")!;
    this.sendSupportRequestIncludeLog = <HTMLInputElement>document.getElementById("supportRequestIncludeLog")!;
    this.settingsBlocks = document.querySelectorAll('[id="_settings_block"]')!;
    this.startExportButton = document.getElementById("startExportButton")!;
    this.targetFolder = <HTMLInputElement>document.getElementById("targetFolder")!;
    this.vendorNotices =
      document.querySelectorAll(".update-nag, .updated, .error, .is-dismissible, .elementor-message")!;

  }
}

