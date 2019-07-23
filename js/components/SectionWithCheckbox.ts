import { WP2StaticGlobals } from "../WP2StaticGlobals"
import { FieldSetWithCheckbox } from "./FieldSetWithCheckbox"

export class SectionWithCheckbox {

  public wp2staticGlobals: WP2StaticGlobals

  public getComponent () {
    const fieldSetWithCheckbox: FieldSetWithCheckbox =
      new FieldSetWithCheckbox(this.wp2staticGlobals)

    return {
      data: () => {
        return {
          count: 0,
        }
      },
      components: {
        FieldSetWithCheckbox: fieldSetWithCheckbox.getComponent(),
      },
      props: [
        "checked",
        "description",
        "hint",
        "id",
        "title",
      ],
      template: `
<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2>{{ title }}</h2>
  </div>

  <div class="content">

    <field-set-with-checkbox 
        :id="id"
        :description="description"
        :hint="hint"
        :checked="checked"
    ></field-set-with-checkbox>

  </div>
</section>`,
    }
  }

  constructor( wp2staticGlobals: WP2StaticGlobals ) {
      this.wp2staticGlobals = wp2staticGlobals
  }

}
