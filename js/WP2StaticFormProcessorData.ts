export class WP2StaticFormProcessorData {

  public formProcessorData: any = [
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

  constructor() {
    return this.formProcessorData
  }

}
