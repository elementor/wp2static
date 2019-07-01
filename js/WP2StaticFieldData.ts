export class WP2StaticFieldData {

  public fieldData: any = {
    allowOfflineUsage: {
      description: `Destination URL will be ignored. Must combine with
 Document-Relative URLs option. <code>index.html</code> will be appended to
 all directory paths`,
      hint: `Check this if you're going to run your site locally,
 ie on a USB drive given to a client.`,
      title: "Allow offline usage",
    },
    completionEmail: {
      description: "Be alerted when your deployment process is complete.",
      hint: "Will send to: ##USER EMAIL##",
      title: "Email upon completion",
    },
    createEmptyFavicon: {
      description: `If you don't have a favicon for your site, block extra
 requests taking up speed.`,
      hint: "Insert empty icon rel to prevent favicon requests",
    },
    displayDashboardWidget: {
      description: `Show a widget on your WordPress dashboard for quickly
 triggering a manual deploy and showing recent deploy information.`,
      hint: "Enable WP2Static dashboard widget",
      title: "Show deploy widget on WP dashboard",
    },
    includeDiscoveredAssets: {
      description: `As we crawl the site, force-include any static assets
 found within the page (images, fonts, css, etc). Must have a supported file
 extension to be included.`,
      hint: "Include Discovered Assets",
      title: "Include Discovered Assets",
    },
    forceRewriteSiteURLs: {
      description: `This is a last-resort method to rewrite any Site URLs that
 weren't able to be intelligently rewritten. This can be the case when the
 Site URL is within a custom HTML tag that WP2Static doesn't know how to
 handle, or within some inline CSS or JavaScript sections, for example.`,
      hint: "Force rewriting any left-over Site URLs to your Destination URL",
    },
    parse_css: {
      description: `This will result in better exports, but will consume
 more memory on the server. Try disabling this if you're unable to complete
 your export and suspect it's running out of memory.`,
      hint: "Parse CSS files",
    },
    forceHTTPS: {
      description: `If you are left with a few remaining http protocol links
 in your exported site and are unable to fix in the original WordPress site,
 this option will force rewrite any links in the exported pages that start
 with http to https. Warning, this is a brute force approach and may alter
 texts on the page that should not be rewritten.`,
      hint: "Force rewriting any http links to https",
    },
    redeployOnPostUpdates: {
      description: `With Crawl and Deploy Caches enabled, only the files
 changed since your last deployment need processing. Choose which actions
 in WordPress will trigger a staging redeployment:`,
      hint: "When a post is created/updated",
      title: "Re-deploy when site changes",
    },
    removeCanonical: {
      description: `Search engines use the canonical tag to identify how to index a page.
 i.e domain.com/page/ and domain.com/page/index.html are 2 different URLs that represent the same page.
 This could trigger a duplicate content penalty. 
 The canonical tag tells the search engine that they are same page and they should be indexed
 as domain.com/page/`,
      hint: "Remove Canonical tags from pages (best left unchecked)",
    },
    removeConditionalHeadComments: {
      description: `Mostly obsolete, previously used for detecting versions of
 Internet Explorer and serving different CSS or JS.`,
      hint: "Remove conditional comments within HEAD",
    },
    removeHTMLComments: {
      description: `ie, <code>&lt;!-- / Yoast SEO plugin. --&gt;</code> type comments
 that are ridiculously wasting bytes`,
      hint: "Remove HTML comments",
    },
    removeWPMeta: {
      description: `The <code>&lt;meta&gt; name="generator"
 content="WordPress 4.9.8" /&gt;</code> type tags.`,
      hint: "Remove WP Meta tags",
    },
    removeWPLinks: {
      description: `ie, <code>&lt;link& rel="EditURI"...</code> type tags that usually aren't needed.`,
      hint: "Remove WP &lt;link&gt; tags",
    },
    useBasicAuth: {
      description: "",
      hint: "My WP site requires Basic Auth to access",
      title: "Use basic authentication",
    },
    useDocumentRelativeURLs: {
      description: `URLs in the exported site will be rewritten as
 <a href="https://www.w3schools.com/tags/tag_base.asp" target="_blank">relative URLs</a>.
 ie, <code>http://mydomain.com/some_dir/some_file.jpg</code> will become
 <code>some_dir/some_file.jpg</code>`,
      hint: "Use document-relative URLs",
      title: "Use document-relative URLs",
    },
    useSiteRootRelativeURLs: {
      description: `URLs in the exported site will be rewritten as site
 root-relative. ie, <code>http://mydomain.com/some_dir/some_file.jpg</code>
 will become <code>/some_dir/some_file.jpg</code>`,
      hint: "Use site root-relative URLs",
      title: "Use site-root relative URLs",
    },
  }

  constructor() {
    return this.fieldData
  }

}
