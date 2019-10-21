declare var wp2staticString: any
declare var ajaxurl: string
import Vue from "vue"
import { DetectionCheckbox } from "./components/DetectionCheckbox"
import { FieldSetWithCheckbox } from "./components/FieldSetWithCheckbox"
import { SectionWithCheckbox } from "./components/SectionWithCheckbox"
import { WP2StaticAdminPageModel } from "./WP2StaticAdminPageModel"
import { WP2StaticAJAX } from "./WP2StaticAJAX"
import { WP2StaticGlobals } from "./WP2StaticGlobals"
import { WP2StaticOptions } from "./WP2StaticOptions"
import { WP2StaticViewData } from "./WP2StaticViewData"

// allow Add-Ons to write to WP2Static.wp2staticGlobals
export const wp2staticGlobals = new WP2StaticGlobals()

let validationErrors = ""

const protocolAndDomainRE = /^(?:\w+:)?\/\/(\S+)$/
const localhostDomainRE = /^localhost[:?\d]*(?:[^:?\d]\S*)?$/
const nonLocalhostDomainRE = /^[^\s.]+\.\S{2,}$/

document.addEventListener("DOMContentLoaded", () => {
    const adminPage = new WP2StaticAdminPageModel()
    wp2staticGlobals.adminPage = adminPage

    wp2staticGlobals.vueData = new WP2StaticViewData()
    wp2staticGlobals.vueData.siteInfo = JSON.parse(wp2staticString.siteInfo)

    // load options from DB which have been rendered into wp2staticString
    const wp2staticOptions = new WP2StaticOptions(wp2staticString.options)

    // merge DB options into vueData
    Object.assign(wp2staticGlobals.vueData, wp2staticOptions)

    const detectionCheckbox: DetectionCheckbox = new DetectionCheckbox(wp2staticGlobals)
    const fieldSetWithCheckbox: FieldSetWithCheckbox = new FieldSetWithCheckbox(wp2staticGlobals)
    const sectionWithCheckbox: SectionWithCheckbox = new SectionWithCheckbox(wp2staticGlobals)

    const vueApp = new Vue({
      components: {
        DetectionCheckbox: detectionCheckbox.getComponent(),
        FieldSetWithCheckbox: fieldSetWithCheckbox.getComponent(),
        SectionWithCheckbox: sectionWithCheckbox.getComponent(),
      },
      data: wp2staticGlobals.vueData,
      el: "#vueApp",
      beforeDestroy: function () {
        this.$el.removeEventListener('change', this.onChange)
        // document.removeEventListener('click', this.onClick)
      },
      computed: {
        baseUrl: () : string => {
          // return 'https://someurl.com'
          const currentBaseURL : HTMLInputElement =
            document.getElementById(
              `baseUrl${wp2staticGlobals.vueData.currentDeploymentMethod}`
            ) as HTMLInputElement

           return currentBaseURL.value
        },
        baseUrlProduction: () : string => {
          // return 'https://someurl.com'
          const currentBaseURLProduction : HTMLInputElement =
            document.getElementById(
              `baseUrlProduction${wp2staticGlobals.vueData.currentDeploymentMethod}`
            ) as HTMLInputElement

           return currentBaseURLProduction.value
        },
      },
      methods: {
        cancelExport: (event: any) => {
          window.location.href = window.location.href
        },
        changeTab: (targetTab: string) => {
          wp2staticGlobals.vueData.currentTab = targetTab

          document.body.scrollTop = 0
          document.documentElement.scrollTop = 0
          if (document.activeElement instanceof HTMLElement) {
              document.activeElement.blur()
          }
        },
        changeTab2: (event: any) => {
          vueApp.changeTab(event.currentTarget.getAttribute("tabid"))
        },
        deleteCrawlCache: (event: any) => {
          wp2staticGlobals.vueData.currentAction = "Deleting Crawl Cache..."

          sendWP2StaticAJAX(
            "delete_crawl_cache",
            deleteCrawlCacheSuccessCallback,
            deleteCrawlCacheFailCallback,
          )
        },
        deleteDeployCache: (event: any) => {
          wp2staticGlobals.vueData.currentAction = "Deleting Deploy Cache..."

          sendWP2StaticAJAX(
            "delete_deploy_cache",
            deleteDeployCacheSuccessCallback,
            deleteDeployCacheFailCallback,
          )
        },
        detectEverything: (event: any) => {
          for ( const checkbox of wp2staticGlobals.vueData.detectionCheckboxes ) {
              wp2staticGlobals.vueData[checkbox.id] = true
          }
        },
        detectNothing: (event: any) => {
          for ( const checkbox of wp2staticGlobals.vueData.detectionCheckboxes ) {
              wp2staticGlobals.vueData[checkbox.id] = false
          }
        },
        generateStaticSite: (event: any) => {
          // set hidden baseUrl to staging current deploy method's Destination URL
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
        resetDefaults: (event: any) => {
          // TODO: set form data

          const form = document.getElementById("general-options")! as HTMLFormElement

          const ajaxActionField = document.createElement("input")
          ajaxActionField.type = "hidden"
          ajaxActionField.name = "ajax_action"
          ajaxActionField.value = "reset_default_settings"
          form.appendChild(ajaxActionField)

          form.submit()
        },
        saveOptions: (event: any) => {
          sendWP2StaticAJAX(
            "save_options",
            saveOptionsSuccessCallback,
            saveOptionsFailCallback,
          )
        },
        stagingChangeMethod: (event: any) => {
          wp2staticGlobals.vueData.currentDeploymentMethod = event.target.value
        },
        startExport: (event: any) => {
          // set hidden baseUrl to staging current deploy method's Destination URL
          wp2staticGlobals.exportCommenceTime = +new Date()

          // TODO: reimplement validators validationErrors = getValidationErrors()
          validationErrors = ""

          if (validationErrors !== "") {
            alert(validationErrors)

            vueApp.$data.progress = false
            vueApp.$data.workflowStatus = "exportStarted"

            return false
          }

          vueApp.$data.currentAction = "Starting export..."

          // reset export targets to avoid having left-overs from a failed run
          wp2staticGlobals.exportTargets = []

          wp2staticGlobals.exportTargets.push(wp2staticGlobals.vueData.currentDeploymentMethod)

          sendWP2StaticAJAX(
            "prepare_for_export",
            startExportSuccessCallback,
            ajaxErrorHandler,
          )
        },
      },
    })

    const wp2staticAJAX = new WP2StaticAJAX( wp2staticGlobals )

    function checkLocalDNSResolutionCallback(event: any) {
      const dnsResolution: string = event.target.response as string

      wp2staticGlobals.vueData.dnsResolution = dnsResolution
    }

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

    function checkLocalDNSResolution() {
      wp2staticGlobals.vueData.currentAction = "Checking site's local DNS resolution"

      sendWP2StaticAJAX(
        "check_local_dns_resolution",
        checkLocalDNSResolutionCallback,
        checkLocalDNSResolutionCallback,
      )
    }

    function prepareInitialFileList() {
      wp2staticGlobals.vueData.currentAction = "Analyzing site... this may take a few minutes (but it's worth it!)"

      sendWP2StaticAJAX(
        "generate_filelist_preview",
        generateFileListSuccessCallback,
        generateFileListFailCallback,
      )
    }

    function checkPublicAccessibility() {
      const request = new XMLHttpRequest()
      request.open(
        "GET",
        `https://api.downfor.cloud/httpcheck/${wp2staticGlobals.vueData.siteInfo.site_url}`,
        true)
      request.onload = checkPubliclyAccessibleCallback
      request.onerror = checkPubliclyAccessibleCallback
      request.send()
    }

    function sendWP2StaticAJAX(ajaxAction: string, successCallback: any, failCallback: any) {
      wp2staticGlobals.vueData.progress = true

      const optionsForm = document.getElementById("general-options")! as HTMLFormElement
      const formData = new FormData(optionsForm)

      formData.set("ajax_action", ajaxAction)
      formData.set("action", "wp_static_html_output_ajax")

      const searchParams = new URLSearchParams(
        // https://github.com/Microsoft/TypeScript/issues/30584
        // @ts-ignore
        // new FormData(optionsForm),
        formData,
      )

      // sort searchParams for easier debugging
      searchParams.sort()

      const data = searchParams.toString()

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

    function checkPubliclyAccessibleCallback(event: any) {
      if (/"isDown":false/.test(event.target.response)) {
        wp2staticGlobals.vueData.publiclyAccessible = "Public"
      } else if (/"isDown":true/.test(event.target.response)) {
        wp2staticGlobals.vueData.publiclyAccessible = "Private"
      } else {
        wp2staticGlobals.vueData.publiclyAccessible = "Unknown"
      }
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

    function getValidationErrors() {
      // check for when targetFolder is showing (plugin reset state)
      if ((adminPage.targetFolder.style.display === "block") &&
            (adminPage.targetFolder.value === "")) {
        validationErrors += "Target folder may not be empty. Please adjust your settings."
      }

      // TODO: should be this.baseUrl now it's computed
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
            wp2staticGlobals.deployOptions[wp2staticGlobals.vueData.currentDeploymentMethod].requiredFields

      if (requiredFields) {
        validateEmptyFields(requiredFields)
      }

      const repoField = wp2staticGlobals.deployOptions[wp2staticGlobals.vueData.currentDeploymentMethod].repoField

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

    // TODO: disable zip base url field when offline usage is checked

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

    function sendSupportSuccessCallback(event: any) {
      alert("Successful support request sent")
    }

    function sendSupportFailCallback(event: any) {
      alert("Failed to send support request. Please try again or contact help@wp2static.com.")
    }

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

    function testDeploymentSuccessCallback(event: any) {
      if (event.target.response === "SUCCESS") {
        alert("Connection/Upload Test Successful")
      } else {
        alert("FAIL: Unable to complete test upload to " + wp2staticGlobals.vueData.currentDeploymentMethod)
      }

      wp2staticGlobals.vueData.progress = false
    }

    function testDeploymentFailCallback(event: any) {
      alert("FAIL: Unable to complete test upload to " + wp2staticGlobals.vueData.currentDeploymentMethod)
      wp2staticGlobals.vueData.progress = false
    }

    // TODO: reimplement handlers for all test_deploy method buttons
    //   need one within each add-on's JS code

    // hide all but WP2Static messages
    hideOtherVendorMessages()

    prepareInitialFileList()

    checkPublicAccessibility()

    checkLocalDNSResolution()
  },
)
