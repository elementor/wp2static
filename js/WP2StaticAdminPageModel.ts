export class WP2StaticAdminPageModel {

  public allowOfflineUsage: HTMLInputElement
  public baseUrl: HTMLInputElement
  public baseUrlZip: HTMLInputElement
  public createZip: HTMLElement
  public downloadZIP: HTMLElement
  public exportDuration: HTMLElement
  public formProcessorDescription: HTMLInputElement
  public formProcessorEndpoint: HTMLInputElement
  public formProcessorSelect: HTMLSelectElement
  public formProcessorWebsite: HTMLInputElement
  public goToDeployTabButton: HTMLElement
  public goToMyStaticSite: HTMLElement
  public hiddenAJAXAction: HTMLInputElement
  public hiddenActionField: HTMLInputElement
  public initialCrawlListCount: HTMLElement
  public initialCrawlListLoader: HTMLElement
  public navigationTabs: NodeListOf<Element>
  public optionsForm: HTMLFormElement
  public previewInitialCrawlListButton: HTMLElement
  public selectedDeploymentMethod: HTMLSelectElement
  public selectedDeploymentMethodProduction: HTMLSelectElement
  public sendSupportRequestButton: HTMLElement
  public sendSupportRequestContent: HTMLInputElement
  public sendSupportRequestEmail: HTMLInputElement
  public sendSupportRequestIncludeLog: HTMLInputElement
  public settingsBlocks: NodeListOf<Element>
  public settingsBlocksProduction: NodeListOf<Element>
  public stagingSummaryDeployMethod: HTMLElement
  public stagingSummaryDeployUrl: HTMLElement
  public productionSummaryDeployMethod: HTMLElement
  public productionSummaryDeployUrl: HTMLElement
  public targetFolder: HTMLInputElement
  public vendorNotices: NodeListOf<Element>

  constructor() {
    this.allowOfflineUsage = document.getElementById("allowOfflineUsage")! as HTMLInputElement
    this.baseUrl = document.getElementById("baseUrl")! as HTMLInputElement
    this.baseUrlZip = document.getElementById("baseUrl-zip")! as HTMLInputElement
    this.createZip = document.getElementById("createZip")!
    this.downloadZIP = document.getElementById("downloadZIP")!
    this.exportDuration = document.getElementById("exportDuration")!
    this.formProcessorDescription = document.getElementById("form_processor_description")! as HTMLInputElement
    this.formProcessorEndpoint = document.getElementById("form_processor_endpoint")! as HTMLInputElement
    this.formProcessorSelect = document.getElementById("form_processor_select")! as HTMLSelectElement
    this.formProcessorWebsite = document.getElementById("form_processor_website")! as HTMLInputElement
    this.goToDeployTabButton = document.getElementById("GoToDeployTabButton")!
    this.goToMyStaticSite = document.getElementById("goToMyStaticSite")!
    this.hiddenAJAXAction = document.getElementById("hiddenAJAXAction")! as HTMLInputElement
    this.hiddenActionField = document.getElementById("hiddenActionField")! as HTMLInputElement
    this.initialCrawlListCount = document.getElementById("initial_crawl_list_count")!
    this.initialCrawlListLoader = document.getElementById("initial_crawl_list_loader")!
    this.navigationTabs = document.querySelectorAll(".nav-tab")!
    this.optionsForm = document.getElementById("general-options")! as HTMLFormElement
    this.previewInitialCrawlListButton = document.getElementById("preview_initial_crawl_list_button")!
    this.selectedDeploymentMethod = document.getElementById("selected_deployment_method")! as HTMLSelectElement
    this.selectedDeploymentMethodProduction =
      document.getElementById("selected_deployment_method_production")! as HTMLSelectElement
    this.sendSupportRequestButton = document.getElementById("send_support_request")! as HTMLInputElement
    this.sendSupportRequestContent = document.getElementById("supportRequestContent")! as HTMLInputElement
    this.sendSupportRequestEmail = document.getElementById("supportRequestEmail")! as HTMLInputElement
    this.sendSupportRequestIncludeLog = document.getElementById("supportRequestIncludeLog")! as HTMLInputElement
    this.settingsBlocks = document.querySelectorAll('[id$="_settings_block"]')!
    this.settingsBlocksProduction = document.querySelectorAll('[id$="_settings_block_production"]')!
    this.stagingSummaryDeployMethod = document.getElementById("stagingSummaryDeployMethod")!
    this.stagingSummaryDeployUrl = document.getElementById("stagingSummaryDeployUrl")!
    this.productionSummaryDeployMethod = document.getElementById("stagingSummaryDeployMethodProduction")!
    this.productionSummaryDeployUrl = document.getElementById("stagingSummaryDeployUrlProduction")!
    this.targetFolder = document.getElementById("targetFolder")! as HTMLInputElement
    this.vendorNotices =
      document.querySelectorAll(".update-nag, .updated, .error, .is-dismissible, .elementor-message")!

  }
}

