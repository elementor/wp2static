import { WP2StaticAdminPageModel } from "./WP2StaticAdminPageModel"

export class WP2StaticGlobals {

  public adminPage: WP2StaticAdminPageModel = new WP2StaticAdminPageModel()
  public exportCommenceTime: number = 0
  public exportCompleteTime: number = 0
  public timerIntervalID: number = 0
  public exportDuration: number = 0
  public statusDescriptions: any = {
    crawl_site: "Crawling initial file list",
    post_export_teardown: "Cleaning up after processing",
    post_process_archive_dir: "Processing the crawled files",
  }
  public currentDeploymentMethod: string = ""
  public currentDeploymentMethodProduction: string = ""
  public siteInfo: any
  public exportTargets: string[] = []
  public deployOptions: any = {
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
  }
  public statusText: string = ""

  public millisToMinutesAndSeconds( millis: number ) {
    const minutes = Math.floor(millis / 60000)
    const seconds: number = parseFloat( ((millis % 60000) / 1000).toFixed(0) )

    return minutes + ":" + (seconds < 10 ? "0" : "") + seconds
  }

}
