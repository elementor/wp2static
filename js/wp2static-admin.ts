declare var wp2staticString: any
declare var ajaxurl: string
import Vue from "vue"
import { WP2StaticAdminPageModel } from "./WP2StaticAdminPageModel"
import { WP2StaticAJAX } from "./WP2StaticAJAX"
import { WP2StaticGlobals } from "./WP2StaticGlobals"

interface FormProcessor {
    id: string
    name: string
    placeholder: string
    website: string
    description: string
}

// NOTE: passing around a globals object to allow shared instance and access
// from browser
// within this entrypoint, access directly. From other classes, this., from
// browser WP2Static.wp2staticGlobals
export const wp2staticGlobals = new WP2StaticGlobals()

const formProcessors: FormProcessor[] = [
  {
    description: "Basin does stuff",
    id: "basin",
    name: "Basin",
    placeholder: "https://usebasin.com/f/",
    website: "https://usebasin.com",
  },
  {
    description: `FormSpree is very simple to start with, just set your
 endpoint, including your email address and start sending.`,
    id: "formspree",
    name: "Formspree",
    placeholder: "https://formspree.io/myemail@domain.com",
    website: "https://formspree.io",
  },
  {
    description: "Zapier does stuff",
    id: "zapier",
    name: "Zapier",
    placeholder: "https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/",
    website: "https://zapier.com",
  },
  {
    description: "Formkeep does stuff",
    id: "formkeep",
    name: "FormKeep",
    placeholder: "https://formkeep.com/f/5dd8de73ce2c",
    website: "https://formkeep.com",
  },
  {
    description: "Use any custom endpoint",
    id: "custom",
    name: "Custom endpoint",
    placeholder: "https://mycustomendpoint.com/SOMEPATH",
    website: "https://docs.wp2static.com",
  },
]

let validationErrors = ""

wp2staticGlobals.siteInfo = JSON.parse(wp2staticString.siteInfo)

if (!wp2staticString.currentDeploymentMethod) {
  wp2staticGlobals.currentDeploymentMethod = "folder"
} else {
  wp2staticGlobals.currentDeploymentMethod = wp2staticString.currentDeploymentMethod
}

if (!wp2staticString.currentDeploymentMethodProduction) {
  wp2staticGlobals.currentDeploymentMethodProduction = "folder"
} else {
  wp2staticGlobals.currentDeploymentMethodProduction = wp2staticString.currentDeploymentMethodProduction
}

// TODO: get the log out of the archive, along with it's meta infos
const logFileUrl = wp2staticGlobals.siteInfo.uploads_url + "wp2static-working-files/EXPORT-LOG.txt"
const selectedFormProcessor = ""
const exportAction = ""
const protocolAndDomainRE = /^(?:\w+:)?\/\/(\S+)$/
const localhostDomainRE = /^localhost[:?\d]*(?:[^:?\d]\S*)?$/
const nonLocalhostDomainRE = /^[^\s.]+\.\S{2,}$/

document.addEventListener("DOMContentLoaded", () => {
    const adminPage = new WP2StaticAdminPageModel()
    wp2staticGlobals.adminPage = adminPage

    wp2staticGlobals.vueData = {
      currentAction: "Starting export...",
      currentDeploymentMethod: "folder",
      currentDeploymentMethodProduction: "folder",
      currentTab: "workflow_tab",
      progress: true,
      tabs: [
        { id: "workflow_tab", name: "Workflow" },
        { id: "url_detection", name: "URL Detection" },
        { id: "crawl_settings", name: "Crawling" },
        { id: "processing_settings", name: "Processing" },
        { id: "form_settings", name: "Forms" },
        { id: "staging_deploy", name: "Staging" },
        { id: "production_deploy", name: "Production" },
        { id: "caching_settings", name: "Caching" },
        { id: "automation_settings", name: "Automation" },
        { id: "advanced_settings", name: "Advanced Options" },
        { id: "add_ons", name: "Add-ons" },
        { id: "help_troubleshooting", name: "Help" },
      ],
    }

    const vueApp = new Vue({
       data: wp2staticGlobals.vueData,
       el: "#vueApp",
       methods: {
         changeTab2: (event: any) => {
          changeTab(event.currentTarget.getAttribute("tabid"))
         },
         detectEverything: (event: any) => {
           const inputs = adminPage.detectionOptionsInputs

           for ( const input of inputs ) {
               input.setAttribute("checked", "")
           }
         },
         generateStaticSite: (event: any) => {
           clearProgressAndResults()
           // set hidden baseUrl to staging current deploy method's Destination URL
           updateBaseUrl()
           wp2staticGlobals.exportCommenceTime = +new Date()

           // TODO: reimplement validators validationErrors = getValidationErrors()
           validationErrors = ""

           if (validationErrors !== "") {
             alert(validationErrors)
             vueApp.$data.progress = false

             return false
           }

           vueApp.$data.currentAction = "Generating Static Site Files..."

           // reset export targets to avoid having left-overs from a failed run
           wp2staticGlobals.exportTargets = []

           sendWP2StaticAJAX(
             "prepare_for_export",
             startExportSuccessCallback,
             ajaxErrorHandler,
           )
         },
         startExport: (event: any) => {
           clearProgressAndResults()
           // set hidden baseUrl to staging current deploy method's Destination URL
           updateBaseUrl()
           wp2staticGlobals.exportCommenceTime = +new Date()

           // TODO: reimplement validators validationErrors = getValidationErrors()
           validationErrors = ""

           if (validationErrors !== "") {
             alert(validationErrors)

             vueApp.$data.progress = false

             return false
           }

           vueApp.$data.currentAction = "Starting export..."

           // reset export targets to avoid having left-overs from a failed run
           wp2staticGlobals.exportTargets = []

           if (wp2staticGlobals.currentDeploymentMethod === "zip") {
             adminPage.createZip.setAttribute("checked", "")
           }

           wp2staticGlobals.exportTargets.push(wp2staticGlobals.currentDeploymentMethod)

           sendWP2StaticAJAX(
             "prepare_for_export",
             startExportSuccessCallback,
             ajaxErrorHandler,
           )
         },
       },
    })

    const wp2staticAJAX = new WP2StaticAJAX( wp2staticGlobals )

    function generateFileListSuccessCallback(event: any) {
      const fileListCount: number = event.target.response as number

      if (!fileListCount) {
        wp2staticGlobals.vueData.progress = false

        wp2staticGlobals.vueData.currentAction = `Failed to generate initial file list.
 Please <a href="https://docs.wp2static.com" target="_blank">contact support</a>`


      } else {
        adminPage.initialCrawlListLoader.style.display = "none"
        adminPage.previewInitialCrawlListButton.style.display = "inline"

        wp2staticGlobals.vueData.progress = false
        wp2staticGlobals.vueData.currentAction = `${fileListCount} URLs were detected for
 initial crawl list. Adjust detection via the URL Detection tab.`

        adminPage.initialCrawlListCount.textContent = `${fileListCount} URLs were
 detected on your site that will be used to initiate the crawl.
 Other URLs will be discovered while crawling.`
      }
    }

    function generateFileListFailCallback(event: any) {
      const failedDeployMessage = `Failed to generate Initial Crawl List.
 Please check your permissions to the WordPress upload directory or check your
 Export Log in case of more info.`

      wp2staticGlobals.vueData.currentAction = failedDeployMessage
      wp2staticGlobals.vueData.progress = false
      adminPage.initialCrawlListLoader.style.display = "none"
    }

    function prepareInitialFileList() {
      wp2staticGlobals.vueData.currentAction = "Analyzing site... this may take a few minutes (but it's worth it!)"

      sendWP2StaticAJAX(
        "generate_filelist_preview",
        generateFileListSuccessCallback,
        generateFileListFailCallback,
      )
    }

    function sendWP2StaticAJAX(ajaxAction: string, successCallback: any, failCallback: any) {
      adminPage.hiddenActionField.value = "wp_static_html_output_ajax"
      adminPage.hiddenAJAXAction.value = ajaxAction
      wp2staticGlobals.vueData.progress = true

      const data = new URLSearchParams(
      // https://github.com/Microsoft/TypeScript/issues/30584
      // @ts-ignore
        new FormData(adminPage.optionsForm),
      ).toString()

      const request = new XMLHttpRequest()
      request.open("POST", ajaxurl, true)
      request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
      request.onload = successCallback
      request.onerror = failCallback
      request.send(data)
    }

    function saveOptionsSuccessCallback(event: any) {
      wp2staticGlobals.vueData.progress = false

      location.reload()
    }

    function saveOptionsFailCallback(event: any) {
      wp2staticGlobals.vueData.progress = false

      location.reload()
    }

    function saveOptions() {
      wp2staticGlobals.vueData.currentAction = "Saving options"
      sendWP2StaticAJAX(
        "save_options",
        saveOptionsSuccessCallback,
        saveOptionsFailCallback,
      )
    }


    function deleteCrawlCacheSuccessCallback(event: any) {
      wp2staticGlobals.vueData.progress = false

      if (!event.target.response) {
        wp2staticGlobals.vueData.currentAction = "Failed to delete Crawl Cache."
      } else {
        wp2staticGlobals.vueData.currentAction = "Crawl Cache successfully deleted."
      }
    }

    function deleteCrawlCacheFailCallback(event: any) {
      wp2staticGlobals.vueData.progress = false
      wp2staticGlobals.vueData.currentAction = "Failed to delete Crawl Cache."
    }

    adminPage.detectNothingButton.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()
        const inputs = adminPage.detectionOptionsInputs

        for ( const input of inputs ) {
            input.removeAttribute("checked")
        }
      },
    )

    adminPage.deleteCrawlCache.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()
        wp2staticGlobals.vueData.currentAction = "Deleting Crawl Cache..."

        sendWP2StaticAJAX(
          "delete_crawl_cache",
          deleteCrawlCacheSuccessCallback,
          deleteCrawlCacheFailCallback,
        )
      },
    )

    function ajaxErrorHandler() {
      const failedDeployMessage = `Failed during ${wp2staticGlobals.statusText}`

      wp2staticGlobals.vueData.progress = false
      wp2staticGlobals.vueData.currentAction = failedDeployMessage
    }

    function startExportSuccessCallback(event: any) {
      const initialSteps = [
        "crawl_site",
        "post_process_archive_dir",
      ]

      wp2staticAJAX.doAJAXExport( initialSteps )
    }

    function clearProgressAndResults() {
      adminPage.downloadZIP.style.display = "none"
      adminPage.goToMyStaticSite.style.display = "none"
      adminPage.exportDuration.style.display = "none"
    }

    function getValidationErrors() {
      // check for when targetFolder is showing (plugin reset state)
      if ((adminPage.targetFolder.style.display === "block") &&
            (adminPage.targetFolder.value === "")) {
        validationErrors += "Target folder may not be empty. Please adjust your settings."
      }

      if ((adminPage.baseUrl.value === undefined ||
            adminPage.baseUrl.value === "") &&
            ! adminPage.allowOfflineUsage.getAttribute("checked")) {
        validationErrors += "Please set the Base URL field to the address you will host your static site.\n"
      }

      if (!isUrl(String(adminPage.baseUrl.value)) && ! adminPage.allowOfflineUsage.getAttribute("checked")) {
        // TODO: testing / URL as base
        if (adminPage.baseUrl.value !== "/") {
          validationErrors += "Please set the Base URL field to a valid URL, ie http://mystaticsite.com.\n"
        }
      }

      const requiredFields =
            wp2staticGlobals.deployOptions[wp2staticGlobals.currentDeploymentMethod].requiredFields

      if (requiredFields) {
        validateEmptyFields(requiredFields)
      }

      const repoField = wp2staticGlobals.deployOptions[wp2staticGlobals.currentDeploymentMethod].repoField

      if (repoField) {
        validateRepoField(repoField)
      }

      return validationErrors
    }

    function validateRepoField(repoField: any) {
      const repositoryField: HTMLInputElement =
        document.getElementById(repoField.field + "")! as HTMLInputElement
      const repo: string = String(repositoryField.value)

      if (repo !== "") {
        if (repo.split("/").length !== 2) {
          validationErrors += repoField.message
        }
      }
    }

    function validateEmptyFields(requiredFields: any) {
      Object.keys(requiredFields).forEach(
        (key, index) => {
          const requiredField: HTMLInputElement =
            document.getElementById(key)! as HTMLInputElement
          if (requiredField.value === "") {
            validationErrors += requiredFields[key] + "\n"
          }
        },
      )
    }

    function isUrl(url: string) {
      const match = url.match(protocolAndDomainRE)

      if (! match) {
        return false
      }

      const everythingAfterProtocol = match[1]

      if (!everythingAfterProtocol) {
        return false
      }

      if (localhostDomainRE.test(everythingAfterProtocol) ||
            nonLocalhostDomainRE.test(everythingAfterProtocol)) {
        return true
      }

      return false
    }

    function hideOtherVendorMessages() {
      Array.prototype.forEach.call(
        adminPage.vendorNotices,
        (element, index) => {
            element.style.display = "none"
            // TODO: ensure any wp2static notices are not mistakenly
            // wp2static-notice
        },
      )
    }

    function setFormProcessor(fp: any) {
      if (fp in formProcessors) {

        const formProcessor: FormProcessor = formProcessors[fp]

        adminPage.formProcessorDescription.textContent = formProcessor.description

        const website = formProcessor.website

        const websiteLink: HTMLAnchorElement  = document.createElement("a")
        websiteLink.setAttribute("href", website)
        websiteLink.innerHTML = "Visit " + formProcessor.name

        adminPage.formProcessorWebsite.innerHTML = website
        adminPage.formProcessorEndpoint.setAttribute("placeholder", formProcessor.placeholder)
      } else {
        adminPage.formProcessorDescription.textContent = ""
        adminPage.formProcessorWebsite.innerHTML = ""
        adminPage.formProcessorEndpoint.setAttribute("placeholder", "Form endpoint")
      }
    }

    function populateFormProcessorOptions(fps: FormProcessor[]) {
      fps.forEach( (formProcessor) => {
        adminPage.formProcessorSelect.options[adminPage.formProcessorSelect.options.length] =
          new Option(formProcessor.name, formProcessor.id)
      })
    }

    function updateBaseUrl() {
      const currentBaseUrlRenameMe: HTMLInputElement | null =
        document.getElementById("baseUrl-" + wp2staticGlobals.currentDeploymentMethod)! as HTMLInputElement

      adminPage.baseUrl.value = currentBaseUrlRenameMe.value
    }

    function updateStagingSummary() {
      adminPage.stagingSummaryDeployMethod.textContent = wp2staticGlobals.currentDeploymentMethod

      const currentBaseUrl: HTMLInputElement | null =
        document.getElementById("baseUrl-" + wp2staticGlobals.currentDeploymentMethod)! as HTMLInputElement

      adminPage.stagingSummaryDeployUrl.textContent = currentBaseUrl.value
    }

    function updateProductionSummary() {
      adminPage.productionSummaryDeployMethod.textContent = wp2staticGlobals.currentDeploymentMethodProduction

      const currentBaseUrlProduction: HTMLInputElement | null =
        document.getElementById("baseUrl-" + wp2staticGlobals.currentDeploymentMethodProduction)! as HTMLInputElement

      adminPage.productionSummaryDeployUrl.textContent = currentBaseUrlProduction.value
    }

    function offlineUsageChangeHandler(checkbox: HTMLElement) {
      if (checkbox.getAttribute("checked")) {
        adminPage.baseUrlZip.setAttribute("disabled", "")
      } else {
        adminPage.baseUrlZip.removeAttribute("disabled")
      }
    }

    function notifyMe() {
      if (!Notification) {
        alert("All exports are complete!.")
        return
      }

      if (window.location.protocol === "https:") {
        if (Notification.permission !== "granted") {
          Notification.requestPermission()
        } else {
          const notification = new Notification(
            "WP Static HTML Export",
            {
              body: "Exports have finished!",
              icon: `https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/
Wordpress_Shiny_Icon.svg/768px-Wordpress_Shiny_Icon.svg.png`,
            },
          )

          notification.onclick = () => {
            parent.focus()
            window.focus()
            notification.close()
          }
        }
      }
    }

    if (Notification.permission !== "granted") {
      if (window.location.protocol === "https:") {
        Notification.requestPermission()
      }
    }

    // disable zip base url field when offline usage is checked
    adminPage.allowOfflineUsage.addEventListener(
      "change",
      (event: any) => {
        offlineUsageChangeHandler(event.currentTarget)
      },
    )

    adminPage.formProcessorSelect.addEventListener(
      "change",
      (event: any) => {
        setFormProcessor((event.currentTarget as HTMLInputElement).value)
      },
    )

    function changeTab(targetTab: string) {
      wp2staticGlobals.vueData.currentTab = targetTab

      document.body.scrollTop = 0
      document.documentElement.scrollTop = 0
      if (document.activeElement instanceof HTMLElement) {
          document.activeElement.blur()
      }
    }

    adminPage.goToDeployTabButton.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()
        changeTab("Deployment")
      },
    )

    // prevent submitting main form outside expected use
    adminPage.generalOptions.addEventListener(
      "submit",
      (event: any) => {
        event.preventDefault()
      },
    )

    adminPage.sendSupportRequestButton.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()

        const supportRequest = adminPage.sendSupportRequestContent.value

        if (adminPage.sendSupportRequestIncludeLog.getAttribute("checked")) {
          /*
          $.get(
            logFileUrl,
            (data) => {
              supportRequest += "#### EXPORT LOG ###"
              supportRequest += data

              data = {
                email: $("#supportRequestEmail").val(),
                supportRequest,
              }

              $.ajax(
                {
                  data,
                  dataType: "html",
                  error: sendSupportFailCallback,
                  method: "POST",
                  success: sendSupportSuccessCallback,
                  url: "https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/",
                },
              )
            },
          )
          */
        }

        const postData = {
          email: adminPage.sendSupportRequestEmail.value,
          supportRequest,
        }

        const request = new XMLHttpRequest()
        request.open("POST", "https://hooks.zapier.com/hooks/catch/4977245/jqj3l4/", true)
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
        request.onload = sendSupportSuccessCallback
        request.onerror = sendSupportFailCallback
        request.send(JSON.stringify(postData))
      },
    )

    adminPage.cancelExportButton.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()
        const reallyCancel = confirm("Stop current export and reload page?")
        if (reallyCancel) {
          window.location.href = window.location.href
        }
      },
    )

    function sendSupportSuccessCallback(event: any) {
      alert("Successful support request sent")
    }

    function sendSupportFailCallback(event: any) {
      alert("Failed to send support request. Please try again or contact help@wp2static.com.")
    }

    function resetDefaultSettingsSuccessCallback(event: any) {
      alert("Settings have been reset to default, the page will now be reloaded.")
      window.location.reload(true)
    }

    function resetDefaultSettingsFailCallback(event: any) {
      alert("Error encountered in trying to reset settings. Please try refreshing the page.")
    }

    adminPage.resetDefaultSettingsButton.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()

        sendWP2StaticAJAX(
          "reset_default_settings",
          resetDefaultSettingsSuccessCallback,
          resetDefaultSettingsFailCallback,
        )
      },
    )

    adminPage.saveSettingsButton.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()
        saveOptions()
      },
    )

    function deleteDeployCacheSuccessCallback(event: any) {
      if (event.target.response === "SUCCESS") {
        alert("Deploy cache cleared")
      } else {
        alert("FAIL: Unable to delete deploy cache")
      }

      wp2staticGlobals.vueData.progress = false
    }

    function deleteDeployCacheFailCallback(event: any) {
      alert("FAIL: Unable to delete deploy cache")

      wp2staticGlobals.vueData.progress = false
    }

    adminPage.deleteDeployCache.addEventListener(
      "click",
      (event: any) => {
        event.preventDefault()
        const button = event.currentTarget
        sendWP2StaticAJAX(
          "delete_deploy_cache",
          deleteDeployCacheSuccessCallback,
          deleteDeployCacheFailCallback,
        )
      },
    )

    function testDeploymentSuccessCallback(event: any) {
      if (event.target.response === "SUCCESS") {
        alert("Connection/Upload Test Successful")
      } else {
        alert("FAIL: Unable to complete test upload to " + wp2staticGlobals.currentDeploymentMethod)
      }

      wp2staticGlobals.vueData.progress = false
    }

    function testDeploymentFailCallback(event: any) {
      alert("FAIL: Unable to complete test upload to " + wp2staticGlobals.currentDeploymentMethod)
      wp2staticGlobals.vueData.progress = false
    }

    /* TODO: reimplement handlers for all test_deploy method buttons
       need one within each add-on's JS code
    $(".wrap").on(
      "click",
      '[id$="-test-button"]',
      (event: any) => {
        event.preventDefault()

        sendWP2StaticAJAX(
          "test_" + wp2staticGlobals.currentDeploymentMethod,
          testDeploymentSuccessCallback,
          testDeploymentFailCallback,
        )
      },
    )
    */

    // guard against selected option for add-on not currently activated
    const deployBaseUrl: HTMLInputElement | null =
      document.getElementById("baseUrl-" + wp2staticGlobals.currentDeploymentMethod)! as HTMLInputElement
    if (deployBaseUrl === null) {
      wp2staticGlobals.currentDeploymentMethod = "folder"
    }

    populateFormProcessorOptions(formProcessors)

    setFormProcessor(selectedFormProcessor)

    // call change handler on page load, to set correct state
    const offlineUsageCheckbox: any = document.getElementById("allowOfflineUsage")
    if ( offlineUsageCheckbox ) {
      offlineUsageChangeHandler(offlineUsageCheckbox)
    }

    // hide all but WP2Static messages
    hideOtherVendorMessages()

    prepareInitialFileList()
  },
)
