import { WP2StaticFieldData } from "./WP2StaticFieldData"
import { WP2StaticFormProcessorData } from "./WP2StaticFormProcessorData"

export class WP2StaticViewData {

  public viewData: any = {
    currentAction: "Starting export...",
    currentTab: "workflow_tab",
    detectionCheckboxes: [
      {
          description: "All published Pages. Use the date range option below to further filter.",
          id: "detectPages",
          title: "Pages",
      },
      {
          description: "All published Posts. Use the date range option below to further filter.",
          id: "detectPosts",
          title: "Posts",
      },
      {
          description: "Include URLs for all Custom Post Types.",
          id: "detectCustomPostTypes",
          title: "Custom Post Types",
      },
      {
          description: "RSS/Atom feeds, such as <code>mydomain.com/some-post/feed/</code>.",
          id: "detectFeedURLs",
          title: "Feed URLs",
      },
      {
          description: "Vendor cache dirs, as used by Autoptimize and certain themes to store images and assets.",
          id: "detectVendorCacheDirs",
          title: "Vendor cache",
      },
      {
          description: "The additional URLs for attachments, such as images. Usually not needed.",
          id: "detectAttachments",
          title: "Attachment URLs",
      },
      {
          description: "All Archive pages, such as Post Categories and Date Archives, etc.",
          id: "detectArchives",
          title: "Archive URLs",
      },
      {
          description: "Get all paginated URLs for Posts.",
          id: "detectPostPagination",
          title: "Posts Pagination",
      },
      {
          description: "Get all paginated URLs for Categories.",
          id: "detectCategoryPagination",
          title: "Category Pagination",
      },
      {
          description: "Get all URLs for Comments.",
          id: "detectComments",
          title: "Comment URLs",
      },
      {
          description: "Get all paginated URLs for Comments.",
          id: "detectCommentPagination",
          title: "Comments Pagination",
      },
      {
          description: "Get all URLs within Parent Theme dir.",
          id: "detectParentTheme",
          title: "Parent Theme URLs",
      },
      {
          description: "Get all URLs within Child Theme dir.",
          id: "detectChildTheme",
          title: "Child Theme URLs",
      },
      {
          description: "Get all public URLs for WP uploads dir.",
          id: "detectUploads",
          title: "Uploads URLs",
      },
      {
          description: "Detect all assets from within all plugin directories.",
          id: "detectPluginAssets",
          title: "Plugin Assets",
      },
      {
          description: "Get all public URLs for wp-includes assets.",
          id: "detectWPIncludesAssets",
          title: "WP-INC JS",
      },
    ],
    fieldData: new WP2StaticFieldData(),
    formProcessorData: new WP2StaticFormProcessorData(),
    progress: true,
    workflowStatus: '',
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

  constructor() {
    return this.viewData
  }

}
