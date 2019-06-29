export class WP2StaticFieldData {

  public fieldData: any = {
    allowOfflineUsage: {
      description: `Destination URL will be ignored. Must combine with
 Document-Relative URLs option. <code>index.html</code> will be appended to all directory paths`,
      hint: "Check this if you're going to run your site locally, ie on a USB drive given to a client.",
      id: "allowOfflineUsage",
      title: "Allow offline usage",
    },
    completionEmail: {
      description: "Be alerted when your deployment process is complete.",
      hint: "Will send to: ##USER EMAIL##",
      id: "completionEmail",
      title: "Email upon completion",
    },
    displayDashboardWidget: {
      description: `Show a widget on your WordPress dashboard for quickly
 triggering a manual deploy and showing recent deploy information.`,
      hint: "Enable WP2Static dashboard widget",
      id: "displayDashboardWidget",
      title: "Show deploy widget on WP dashboard",
    },
    includeDiscoveredAssets: {
      description: `As we crawl the site, force-include any static assets
 found within the page (images, fonts, css, etc). Must have a supported file extension to be included.`,
      hint: "Include Discovered Assets",
      id: "includeDiscoveredAssets",
      title: "Include Discovered Assets",
    },
    useBasicAuth: {
      description: "",
      hint: "My WP site requires Basic Auth to access",
      id: "useBasicAuth",
      title: "Use basic authentication",
    },
    useDocumentRelativeURLs: {
      description: `URLs in the exported site will be rewritten as
 <a href="https://www.w3schools.com/tags/tag_base.asp" target="_blank">relative URLs</a>.
 ie, <code>http://mydomain.com/some_dir/some_file.jpg</code> will become
 <code>some_dir/some_file.jpg</code>`,
      hint: "Use document-relative URLs",
      id: "useDocumentRelativeURLs",
      title: "Use document-relative URLs",
    },
    useSiteRootRelativeURLs: {
      description: `URLs in the exported site will be rewritten as site
 root-relative. ie, <code>http://mydomain.com/some_dir/some_file.jpg</code>
 will become <code>/some_dir/some_file.jpg</code>`,
      hint: "Use site root-relative URLs",
      id: "useSiteRootRelativeURLs",
      title: "Use site-root relative URLs",
    },
  }

  constructor() {
    return this.fieldData
  }

}
